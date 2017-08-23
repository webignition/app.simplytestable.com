<?php
namespace SimplyTestable\ApiBundle\Command\Job;

use Psr\Log\LoggerInterface;
use SimplyTestable\ApiBundle\Exception\Services\JobPreparation\Exception as JobPreparationException;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\CrawlJobContainerService;
use SimplyTestable\ApiBundle\Services\JobPreparationService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\Resque\JobFactoryService as ResqueJobFactory;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;

class PrepareCommand extends Command
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_CANNOT_PREPARE_IN_WRONG_STATE = 1;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 2;

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
     * @var JobService
     */
    private $jobService;

    /**
     * @var JobPreparationService
     */
    private $jobPreparationService;

    /**
     * @var CrawlJobContainerService
     */
    private $crawlJobContainerService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $predefinedDomainsToIgnore;

    /**
     * @param ApplicationStateService $applicationStateService
     * @param ResqueQueueService $resqueQueueService
     * @param ResqueJobFactory $resqueJobFactory
     * @param JobService $jobService
     * @param JobPreparationService $jobPreparationService
     * @param CrawlJobContainerService $crawlJobContainerService
     * @param LoggerInterface $logger
     * @param array $predefinedDomainsToIgnore
     * @param string|null $name
     */
    public function __construct(
        ApplicationStateService $applicationStateService,
        ResqueQueueService $resqueQueueService,
        ResqueJobFactory $resqueJobFactory,
        JobService $jobService,
        JobPreparationService $jobPreparationService,
        CrawlJobContainerService $crawlJobContainerService,
        LoggerInterface $logger,
        $predefinedDomainsToIgnore,
        $name = null
    ) {
        parent::__construct($name);

        $this->applicationStateService = $applicationStateService;
        $this->resqueQueueService = $resqueQueueService;
        $this->resqueJobFactory = $resqueJobFactory;
        $this->jobService = $jobService;
        $this->jobPreparationService = $jobPreparationService;
        $this->crawlJobContainerService = $crawlJobContainerService;
        $this->logger = $logger;
        $this->predefinedDomainsToIgnore = $predefinedDomainsToIgnore;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:job:prepare')
            ->setDescription('Prepare a set of tasks for a given job')
            ->addArgument('id', InputArgument::REQUIRED, 'id of job to prepare')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->applicationStateService->isInMaintenanceReadOnlyState()) {
            $this->resqueQueueService->enqueue(
                $this->resqueJobFactory->create(
                    'job-prepare',
                    ['id' => (int)$input->getArgument('id')]
                )
            );

            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $this->logger->info(sprintf(
            'simplytestable:job:prepare running for job [%s]',
            $input->getArgument('id')
        ));

        $job = $this->jobService->getById((int)$input->getArgument('id'));

        foreach ($job->getRequestedTaskTypes() as $taskType) {
            /* @var TaskType $taskType */
            $taskTypeKey = strtolower(str_replace(' ', '-', $taskType->getName()));

            if (isset($this->predefinedDomainsToIgnore[$taskTypeKey])) {
                $predefinedDomainsToIgnore = $this->predefinedDomainsToIgnore[$taskTypeKey];
                $this->jobPreparationService->setPredefinedDomainsToIgnore($taskType, $predefinedDomainsToIgnore);
            }
        }

        try {
            $this->jobPreparationService->prepare($job);

            if ($job->getTasks()->count()) {
                $this->resqueQueueService->enqueue(
                    $this->resqueJobFactory->create(
                        'tasks-notify'
                    )
                );
            } else {
                if ($this->crawlJobContainerService->hasForJob($job)) {
                    $crawlJob = $this->crawlJobContainerService->getForJob($job)->getCrawlJob();

                    $this->resqueQueueService->enqueue(
                        $this->resqueJobFactory->create(
                            'task-assign-collection',
                            ['ids' => $crawlJob->getTasks()->first()->getId()]
                        )
                    );
                }
            }

            $this->logger->info(sprintf(
                'simplytestable:job:prepare: queued up [%s] tasks covering [%s] urls and [%s] task types',
                $job->getTasks()->count(),
                $job->getUrlCount(),
                count($job->getRequestedTaskTypes())
            ));
        } catch (JobPreparationException $jobPreparationServiceException) {
            $this->logger->info(sprintf(
                'simplytestable:job:prepare: nothing to do, job has a state of [%s]',
                $job->getState()->getName()
            ));

            return self::RETURN_CODE_CANNOT_PREPARE_IN_WRONG_STATE;
        }

        return self::RETURN_CODE_OK;
    }
}
