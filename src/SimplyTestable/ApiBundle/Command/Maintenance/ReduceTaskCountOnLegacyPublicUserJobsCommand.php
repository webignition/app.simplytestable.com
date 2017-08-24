<?php
namespace SimplyTestable\ApiBundle\Command\Maintenance;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Repository\JobRepository;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\JobUserAccountPlanEnforcementService;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use SimplyTestable\ApiBundle\Services\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Job\Ammendment;

class ReduceTaskCountOnLegacyPublicUserJobsCommand extends Command
{
    const RETURN_CODE_OK = 0;
    const DEFAULT_TASK_REMOVAL_GROUP_SIZE = 1000;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var UserAccountPlanService
     */
    private $userAccountPlanService;

    /**
     * @var JobService
     */
    private $jobService;

    /**
     * @var JobTypeService
     */
    private $jobTypeService;

    /**
     * @var TaskService
     */
    private $taskService;

    /**
     * @var StateService
     */
    private $stateService;

    /**
     * @var JobUserAccountPlanEnforcementService
     */
    private $jobUserAccountPlanEnforcementService;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param UserService $userService
     * @param UserAccountPlanService $userAccountPlanService
     * @param JobService $jobService
     * @param JobTypeService $jobTypeService
     * @param TaskService $taskService
     * @param StateService $stateService
     * @param JobUserAccountPlanEnforcementService $jobUserAccountPlanEnforcementService
     * @param EntityManager $entityManager
     * @param null $name
     */
    public function __construct(
        UserService $userService,
        UserAccountPlanService $userAccountPlanService,
        JobService $jobService,
        JobTypeService $jobTypeService,
        TaskService $taskService,
        StateService $stateService,
        JobUserAccountPlanEnforcementService $jobUserAccountPlanEnforcementService,
        EntityManager $entityManager,
        $name = null
    ) {
        parent::__construct($name);

        $this->userService = $userService;
        $this->userAccountPlanService = $userAccountPlanService;
        $this->jobService = $jobService;
        $this->jobTypeService = $jobTypeService;
        $this->taskService = $taskService;
        $this->stateService = $stateService;
        $this->jobUserAccountPlanEnforcementService = $jobUserAccountPlanEnforcementService;
        $this->entityManager = $entityManager;
    }

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
            ->addOption(
                'task-removal-group-size',
                null,
                InputOption::VALUE_OPTIONAL,
                'How many tasks to remove at once',
                self::DEFAULT_TASK_REMOVAL_GROUP_SIZE
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;

        /* @var JobRepository $jobRepository */
        $jobRepository = $this->entityManager->getRepository(Job::class);

        $isDryRun = filter_var($input->getOption('dry-run'), FILTER_VALIDATE_BOOLEAN);

        if ($isDryRun) {
            $output->writeln('<comment>This is a DRY RUN, no data will be written</comment>');
        }

        $user = $this->userService->getPublicUser();
        $userAccountPlan = $this->userAccountPlanService->getForUser($user);
        $plan = $userAccountPlan->getPlan();
        $urlsPerJobConstraint = $plan->getConstraintNamed(
            JobUserAccountPlanEnforcementService::URLS_PER_JOB_CONSTRAINT_NAME
        );

        $urlLimit = $urlsPerJobConstraint->getLimit();

        $output->writeln('<info>Public user urls_per_job limit is: '.$urlLimit.'</info>');

        $jobIdsToIgnore = explode(',', $this->input->getOption('job-ids-to-ignore'));

        if (!empty($jobIdsToIgnore)) {
            $output->writeln('<info>Ignoring jobs: '.  implode(',', $jobIdsToIgnore).'</info>');
        }

        $output->writeln('');
        $output->write('Finding public user jobs to check ... ');

        $jobIdsToCheck = $jobRepository->getIdsByUserAndTypeAndNotStates(
            $user,
            $this->jobTypeService->getByName('full site'),
            $this->stateService->fetchCollection([
                JobService::FAILED_NO_SITEMAP_STATE,
                JobService::REJECTED_STATE
            ])
        );

        $output->writeln(count($jobIdsToCheck).' found');

        $totalTasksRemovedCount = 0;
        $completedJobCount = 0;

        foreach ($jobIdsToCheck as $jobIndex => $jobId) {
            $completedJobCount++;

            if (in_array($jobId, $jobIdsToIgnore)) {
                continue;
            }

            $output->write('Checking job ['.$jobId.'] ['.$completedJobCount.' of '.count($jobIdsToCheck).'] ... ');

            $job = $this->jobService->getById($jobId);
            $urlCount = $this->taskService->getUrlCountByJob($job);

            if ($urlCount <= $urlLimit) {
                $output->writeln('ok');
                $this->jobService->getManager()->detach($job);
                continue;
            }

            if (!$this->hasPlanUrlLimitReachedAmmendment($job) && !$isDryRun) {
                $this->jobUserAccountPlanEnforcementService->setUser($user);
                $this->jobService->addAmmendment(
                    $job,
                    'plan-url-limit-reached:discovered-url-count-' . $urlCount,
                    $urlsPerJobConstraint
                );
                $this->jobService->getManager()->flush();
            }

            $taskCount = $this->taskService->getCountByJob($job);
            $output->write('Has ['.$urlCount.'] urls and ['.$taskCount.'] tasks, ');

            $urlsToKeep = $this->getUrlsToKeep($job, $urlLimit, $this->taskService);

            $taskIdsToRemove = $this->taskService->getEntityRepository()->getIdsByJobAndUrlExclusionSet(
                $job,
                $urlsToKeep
            );

            $output->write('removing ['.count($taskIdsToRemove).'] tasks ');

            $taskRemovalGroupSize = $input->getOption('task-removal-group-size');

            $taskRemovalGroups = $this->getTaskRemovalGroups($taskIdsToRemove, $taskRemovalGroupSize);

            foreach ($taskRemovalGroups as $taskIdGroupIndex => $taskIdGroup) {
                foreach ($taskIdGroup as $taskId) {
                    /* @var $task \SimplyTestable\ApiBundle\Entity\Task\Task */
                    $task = $this->taskService->getById($taskId);


                    $this->taskService->getManager()->remove($task);
                    $this->taskService->getManager()->detach($task);
                }

                if (!$isDryRun) {
                    $this->taskService->getManager()->flush();
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
     * @param int $taskRemovalGroupSize
     *
     * @return array
     */
    private function getTaskRemovalGroups($taskIdsToRemove, $taskRemovalGroupSize)
    {
        $taskRemovalGroups = array();

        if (count($taskIdsToRemove) <= $taskRemovalGroupSize) {
            $taskRemovalGroups[] = $taskIdsToRemove;

            return $taskRemovalGroups;
        }

        $currentRemovalGroup = array();

        foreach ($taskIdsToRemove as $taskIdIndex => $taskId) {
            if ($taskIdIndex % $taskRemovalGroupSize === 0 && $taskIdIndex > 0) {
                $taskRemovalGroups[] = $currentRemovalGroup;
                $currentRemovalGroup = array();
            }

            $currentRemovalGroup[] = $taskId;
        }

        $taskRemovalGroups[] = $currentRemovalGroup;

        return $taskRemovalGroups;
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
}
