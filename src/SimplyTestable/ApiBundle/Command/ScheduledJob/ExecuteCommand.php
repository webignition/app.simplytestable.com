<?php
namespace SimplyTestable\ApiBundle\Command\ScheduledJob;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\Resque\JobFactory as ResqueJobFactory;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SimplyTestable\ApiBundle\Services\Job\StartService as JobStartService;
use SimplyTestable\ApiBundle\Exception\Services\Job\UserAccountPlan\Enforcement\Exception
    as UserAccountPlanEnforcementException;
use SimplyTestable\ApiBundle\Exception\Services\Job\Start\Exception as JobStartServiceException;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint as AccountPlanConstraint;

class ExecuteCommand extends Command
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 2;
    const RETURN_CODE_INVALID_SCHEDULED_JOB = 3;
    const RETURN_CODE_UNROUTABLE = 4;
    const RETURN_CODE_PLAN_LIMIT_REACHED = 5;

    /**
     * @var ApplicationStateService
     */
    private $applicationStateService;

    /**
     * @var ResqueQueueService
     */
    private $resqueQueueService;

    /**
     * @var ResqueJobFactory
     */
    private $resqueJobFactory;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var JobStartService
     */
    private $jobStartService;

    /**
     * @var JobService
     */
    private $jobService;

    /**
     * @var EntityRepository
     */
    private $scheduledJobRepository;

    /**
     * @param ApplicationStateService $applicationStateService
     * @param ResqueQueueService $resqueQueueService
     * @param ResqueJobFactory $resqueJobFactory
     * @param EntityManagerInterface $entityManager
     * @param JobStartService $jobStartService
     * @param JobService $jobService
     * @param string|null $name
     */
    public function __construct(
        ApplicationStateService $applicationStateService,
        ResqueQueueService $resqueQueueService,
        ResqueJobFactory $resqueJobFactory,
        EntityManagerInterface $entityManager,
        JobStartService $jobStartService,
        JobService $jobService,
        $name = null
    ) {
        parent::__construct($name);

        $this->applicationStateService = $applicationStateService;
        $this->resqueQueueService = $resqueQueueService;
        $this->resqueJobFactory = $resqueJobFactory;
        $this->entityManager = $entityManager;
        $this->jobStartService = $jobStartService;
        $this->jobService = $jobService;
        $this->scheduledJobRepository = $entityManager->getRepository(ScheduledJob::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:scheduledjob:execute')
            ->setDescription('Start a new job from a scheduled job')
            ->addArgument('id', InputArgument::REQUIRED, 'id of scheduled job to execute')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $id = (int)$input->getArgument('id');

        if ($this->applicationStateService->isInMaintenanceReadOnlyState()) {
            if (!$this->resqueQueueService->contains('scheduled-job', ['id' => $id])) {
                $this->resqueQueueService->enqueue(
                    $this->resqueJobFactory->create(
                        'scheduledjob-execute',
                        ['id' => $id]
                    )
                );
            }

            $output->writeln('In maintenance read-only mode, re-queueing');

            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        /* @var ScheduledJob $scheduledJob */
        $scheduledJob = $this->scheduledJobRepository->find($id);

        if (empty($scheduledJob)) {
            $output->writeln('Scheduled job [' . $input->getArgument('id') . '] does not exist');

            return self::RETURN_CODE_INVALID_SCHEDULED_JOB;
        }

        try {
            $this->jobStartService->start($scheduledJob->getJobConfiguration());
        } catch (JobStartServiceException $jobStartServiceException) {
            $this->reject($scheduledJob->getJobConfiguration(), 'unroutable');
            $output->writeln('Website [' . $scheduledJob->getJobConfiguration()->getWebsite() . '] is unroutable');

            return self::RETURN_CODE_UNROUTABLE;
        } catch (UserAccountPlanEnforcementException $userAccountPlanEnforcementException) {
            $this->reject(
                $scheduledJob->getJobConfiguration(),
                'plan-constraint-limit-reached',
                $userAccountPlanEnforcementException->getAccountPlanConstraint()
            );

            $output->writeln(sprintf(
                'Plan limit [%s] reached',
                $userAccountPlanEnforcementException->getAccountPlanConstraint()->getName()

            ));

            return self::RETURN_CODE_PLAN_LIMIT_REACHED;
        }

        return self::RETURN_CODE_OK;
    }

    /**
     * @param JobConfiguration $jobConfiguration
     * @param string $reason
     * @param AccountPlanConstraint $constraint
     */
    private function reject(JobConfiguration $jobConfiguration, $reason, AccountPlanConstraint $constraint = null)
    {
        $job = $this->jobService->create(
            $jobConfiguration
        );

        $this->jobService->reject($job, $reason, $constraint);
    }
}
