<?php
namespace SimplyTestable\ApiBundle\Command\ScheduledJob;

use SimplyTestable\ApiBundle\Command\BaseCommand;
use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SimplyTestable\ApiBundle\Services\ScheduledJob\Service as ScheduledJobService;
use SimplyTestable\ApiBundle\Services\Job\StartService as JobStartService;
use SimplyTestable\ApiBundle\Exception\Services\Job\UserAccountPlan\Enforcement\Exception as UserAccountPlanEnforcementException;
use SimplyTestable\ApiBundle\Exception\Services\Job\Start\Exception as JobStartServiceException;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint as AccountPlanConstraint;

class ExecuteCommand extends BaseCommand
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 2;
    const RETURN_CODE_INVALID_SCHEDULED_JOB = 3;
    const RETURN_CODE_UNROUTABLE = 4;
    const RETURN_CODE_PLAN_LIMIT_REACHED = 5;

    protected function configure()
    {
        $this
            ->setName('simplytestable:scheduledjob:execute')
            ->setDescription('Start a new job from a scheduled job')
            ->addArgument('id', InputArgument::REQUIRED, 'id of scheduled job to execute')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            if (!$this->getResqueQueueService()->contains('scheduled-job', ['id' => (int)$input->getArgument('id')])) {
                $this->getResqueQueueService()->enqueue(
                    $this->getResqueQueueService()->getJobFactoryService()->create(
                        'scheduledjob-execute',
                        ['id' => (int)$input->getArgument('id')]
                    )
                );
            }

            $output->writeln('In maintenance read-only mode, re-queueing');
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        if (!$this->getScheduledJobService()->getEntityRepository()->find($input->getArgument('id'))) {
            $output->writeln('Scheduled job [' . $input->getArgument('id') . '] does not exist');
            return self::RETURN_CODE_INVALID_SCHEDULED_JOB;
        }

        /* @var $scheduledJob ScheduledJob */
        $scheduledJob = $this->getScheduledJobService()->getEntityRepository()->find($input->getArgument('id'));

        try {
            $this->getJobStartService()->start($scheduledJob->getJobConfiguration());
        } catch (JobStartServiceException $jobStartServiceException) {
            if ($jobStartServiceException->isUnroutableWebsiteException()) {
                $this->reject($scheduledJob->getJobConfiguration(), 'unroutable');
                $output->writeln('Website [' . $scheduledJob->getJobConfiguration()->getWebsite() . '] is unroutable');
                return self::RETURN_CODE_UNROUTABLE;
            }

            throw $jobStartServiceException;
        } catch (UserAccountPlanEnforcementException $userAccountPlanEnforcementException) {
            $this->reject($scheduledJob->getJobConfiguration(), 'plan-constraint-limit-reached', $userAccountPlanEnforcementException->getAccountPlanConstraint());
            $output->writeln('Plan limit [' . $userAccountPlanEnforcementException->getAccountPlanConstraint()->getName() . '] reached');
            return self::RETURN_CODE_PLAN_LIMIT_REACHED;
        }

        return self::RETURN_CODE_OK;
    }


    /**
     * @param JobConfiguration $jobConfiguration
     * @param string $reason
     * @param AccountPlanConstraint $constraint
     */
    private function reject(JobConfiguration $jobConfiguration, $reason, AccountPlanConstraint $constraint = null) {
        $job = $this->getJobService()->create(
            $jobConfiguration
        );

        $jobService = $this->getContainer()->get('simplytestable.services.jobservice');
        $jobService->reject($job, $reason, $constraint);
    }


    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\Resque\QueueService
     */
    private function getResqueQueueService() {
        return $this->getContainer()->get('simplytestable.services.resque.queueService');
    }


    /**
     * @return ScheduledJobService
     */
    private function getScheduledJobService() {
        return $this->getContainer()->get('simplytestable.services.scheduledjob.service');
    }


    /**
     * @return JobStartService
     */
    private function getJobStartService() {
        return $this->getContainer()->get('simplytestable.services.job.startservice');
    }

    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\JobService
     */
    private function getJobService() {
        return $this->getContainer()->get('simplytestable.services.jobservice');
    }

}