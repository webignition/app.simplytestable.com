<?php
namespace SimplyTestable\ApiBundle\Command\Maintenance;

use SimplyTestable\ApiBundle\Repository\JobRepository;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\JobUserAccountPlanEnforcementService;
use SimplyTestable\ApiBundle\Services\TaskService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use SimplyTestable\ApiBundle\Command\BaseCommand;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Job\Ammendment;

class ReduceTaskCountOnLegacyPublicUserJobsCommand extends BaseCommand
{
    const RETURN_CODE_OK = 0;
    const TASK_REMOVAL_GROUP_SIZE = 1000;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:maintenance:reduce-task-count-on-legacy-public-user-jobs')
            ->setDescription('Reduce task count on legacy public user jobs to conform to plan constraints')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_OPTIONAL,
                'Run through the process without writing any data'
            )
            ->addOption(
                'job-ids-to-ignore',
                null,
                InputOption::VALUE_OPTIONAL,
                'Comma-separated list of job ids to ignore'
            )
            ->setHelp('Reduce task count on legacy public user jobs to conform to plan constraints.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;

        $userService = $this->getContainer()->get('simplytestable.services.userservice');
        $userAccountPlanService = $this->getContainer()->get('simplytestable.services.useraccountplanservice');
        $jobService = $this->getContainer()->get('simplytestable.services.jobservice');
        $jobTypeService = $this->getContainer()->get('simplytestable.services.jobtypeservice');
        $taskService = $this->getContainer()->get('simplytestable.services.taskservice');
        $stateService = $this->getContainer()->get('simplytestable.services.stateservice');
        $jobUserAccountPlanEnforcementService = $this->getContainer()->get(
            'simplytestable.services.jobuseraccountplanenforcementservice'
        );
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        /* @var JobRepository $jobRepository */
        $jobRepository = $entityManager->getRepository(Job::class);

        $isDryRun = $this->input->getOption('dry-run') == 'true';

        if ($isDryRun) {
            $output->writeln('<comment>This is a DRY RUN, no data will be written</comment>');
        }

        $user = $userService->getPublicUser();
        $userAccountPlan = $userAccountPlanService->getForUser($user);
        $plan = $userAccountPlan->getPlan();
        $urlsPerJobConstraint = $plan->getConstraintNamed(
            JobUserAccountPlanEnforcementService::URLS_PER_JOB_CONSTRAINT_NAME
        );

        $urlLimit = $urlsPerJobConstraint->getLimit();

        $output->writeln('<info>Public user urls_per_job limit is: '.$urlLimit.'</info>');

        if ($this->hasJobIdsToIgnore()) {
            $output->writeln('<info>Ignoring jobs: '.  implode(',', $this->getJobIdsToIgnore()).'</info>');
        }

        $output->writeln('');

        $output->write('Finding public user jobs to check ... ');

        $jobIdsToCheck = $jobRepository->getIdsByUserAndTypeAndNotStates(
            $user,
            $jobTypeService->getByName('full site'),
            $stateService->fetchCollection([
                JobService::FAILED_NO_SITEMAP_STATE,
                JobService::REJECTED_STATE
            ])
        );

        $output->writeln(count($jobIdsToCheck).' found');

        $totalTasksRemovedCount = 0;
        $completedJobCount = 0;

        foreach ($jobIdsToCheck as $jobId) {
            $completedJobCount++;

            if ($this->isJobIdIgnored($jobId)) {
                continue;
            }

            $output->write('Checking job ['.$jobId.'] ['.$completedJobCount.' of '.count($jobIdsToCheck).'] ... ');

            $job = $jobService->getById($jobId);
            $urlCount = $taskService->getUrlCountByJob($job);

            if ($urlCount <= $urlLimit) {
                $output->writeln('ok');
                $jobService->getManager()->detach($job);
                continue;
            }

            if (!$this->hasPlanUrlLimitReachedAmmendment($job)) {
                $jobUserAccountPlanEnforcementService->setUser($user);
                $jobService->addAmmendment(
                    $job,
                    'plan-url-limit-reached:discovered-url-count-' . $urlCount,
                    $urlsPerJobConstraint
                );
                $jobService->getManager()->flush();
            }

            $taskCount = $taskService->getCountByJob($job);
            $output->write('Has ['.$urlCount.'] urls and ['.$taskCount.'] tasks, ');

            $urlsToKeep = $this->getUrlsToKeep($job, $urlLimit, $taskService);

            $taskIdsToRemove = $taskService->getEntityRepository()->getIdsByJobAndUrlExclusionSet($job, $urlsToKeep);
            $output->write('removing ['.count($taskIdsToRemove).'] tasks ');

            $taskRemovalGroups = $this->getTaskRemovalGroups($taskIdsToRemove);

            foreach ($taskRemovalGroups as $taskIdGroupIndex => $taskIdGroup) {
                foreach ($taskIdGroup as $taskId) {
                    /* @var $task \SimplyTestable\ApiBundle\Entity\Task\Task */
                    $task = $taskService->getById($taskId);
                    $taskService->getManager()->remove($task);
                    $taskService->getManager()->detach($task);
                }

                if (!$isDryRun) {
                    $taskService->getManager()->flush();
                }

                $ratioCompleted = ($taskIdGroupIndex + 1) / count($taskRemovalGroups);

                if ($ratioCompleted > 0 && $ratioCompleted <= 0.25) {
                    $output->write('<fg=green>.</fg=green>');
                }

                if ($ratioCompleted > 0.25 && $ratioCompleted <= 0.50) {
                    $output->write('<fg=blue>.</fg=blue>');
                }

                if ($ratioCompleted > 0.50 && $ratioCompleted <= 0.75) {
                    $output->write('<fg=yellow>.</fg=yellow>');
                }

                if ($ratioCompleted > 0.75) {
                    $output->write('<fg=red>.</fg=red>');
                }
            }

            $output->writeln('');
            $totalTasksRemovedCount += count($taskIdsToRemove);
        }

        $output->writeln('');
        $output->writeln('');
        $output->writeln('<info>============================================</info>');
        $output->writeln('');
        $output->writeln('Tasks removed: ['.$totalTasksRemovedCount.']');

        return self::RETURN_CODE_OK;
    }

    /**
     * @param Job $job
     *
     * @return bool
     */
    private function hasPlanUrlLimitReachedAmmendment(Job $job)
    {
        if (is_null($job->getAmmendments()) || $job->getAmmendments()->count() === 0) {
            return false;
        }

        foreach ($job->getAmmendments() as $ammendment) {
            /* @var $ammendment Ammendment */
            if (substr_count($ammendment->getReason(), 'plan-url-limit-reached')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param int[] $taskIdsToRemove
     *
     * @return array
     */
    private function getTaskRemovalGroups($taskIdsToRemove)
    {
        $taskRemovalGroups = array();

        if (count($taskIdsToRemove) <= self::TASK_REMOVAL_GROUP_SIZE) {
            $taskRemovalGroups[] = $taskIdsToRemove;
            return $taskRemovalGroups;
        }

        $currentRemovalGroup = array();

        foreach ($taskIdsToRemove as $taskIdIndex => $taskId) {
            if ($taskIdIndex % self::TASK_REMOVAL_GROUP_SIZE === 0) {
                $taskRemovalGroups[] = $currentRemovalGroup;
                $currentRemovalGroup = array();
            }

            $currentRemovalGroup[] = $taskId;
        }

        return $taskRemovalGroups;
    }

    /**
     * @param int $jobId
     *
     * @return bool
     */
    private function isJobIdIgnored($jobId)
    {
        return in_array($jobId, $this->getJobIdsToIgnore());
    }

    /**
     * @param Job $job
     * @param int $limit
     * @param TaskService $taskService
     *
     * @return string[]
     */
    private function getUrlsToKeep(Job $job, $limit, TaskService $taskService)
    {
        $urls = $taskService->getUrlsByJob($job);
        $tempUrlsToKeep = array_slice($urls, 0, $limit);
        $urlsToKeep = array();

        foreach ($tempUrlsToKeep as $urlRecord) {
            $urlsToKeep[] = $urlRecord['url'];
        }

        return $urlsToKeep;
    }

    /**
     * @return bool
     */
    private function hasJobIdsToIgnore()
    {
        return !is_null($this->input->getOption('job-ids-to-ignore'));
    }

    /**
     * @return array
     */
    private function getJobIdsToIgnore()
    {
        if (!$this->hasJobIdsToIgnore()) {
            return array();
        }

        return explode(',', $this->input->getOption('job-ids-to-ignore'));
    }
}