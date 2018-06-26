<?php
namespace SimplyTestable\ApiBundle\Command\Job;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Exception\Services\JobPreparation\Exception as JobPreparationException;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\CrawlJobContainerService;
use SimplyTestable\ApiBundle\Services\JobPreparationService;
use webignition\ResqueJobFactory\ResqueJobFactory;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;

class PrepareCommand extends Command
{
    const NAME = 'simplytestable:job:prepare';
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
     * @var JobPreparationService
     */
    private $jobPreparationService;

    /**
     * @var CrawlJobContainerService
     */
    private $crawlJobContainerService;

    /**
     * @var array
     */
    private $predefinedDomainsToIgnore;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @param ApplicationStateService $applicationStateService
     * @param ResqueQueueService $resqueQueueService
     * @param ResqueJobFactory $resqueJobFactory
     * @param JobPreparationService $jobPreparationService
     * @param CrawlJobContainerService $crawlJobContainerService
     * @param EntityManagerInterface $entityManager,
     * @param array $predefinedDomainsToIgnore
     * @param string|null $name
     */
    public function __construct(
        ApplicationStateService $applicationStateService,
        ResqueQueueService $resqueQueueService,
        ResqueJobFactory $resqueJobFactory,
        JobPreparationService $jobPreparationService,
        CrawlJobContainerService $crawlJobContainerService,
        EntityManagerInterface $entityManager,
        $predefinedDomainsToIgnore,
        $name = null
    ) {
        parent::__construct($name);

        $this->applicationStateService = $applicationStateService;
        $this->resqueQueueService = $resqueQueueService;
        $this->resqueJobFactory = $resqueJobFactory;
        $this->jobPreparationService = $jobPreparationService;
        $this->crawlJobContainerService = $crawlJobContainerService;
        $this->predefinedDomainsToIgnore = $predefinedDomainsToIgnore;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Prepare a set of tasks for a given job')
            ->addArgument('id', InputArgument::REQUIRED, 'id of job to prepare')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->applicationStateService->isInReadOnlyMode()) {
            $this->resqueQueueService->enqueue(
                $this->resqueJobFactory->create(
                    'job-prepare',
                    ['id' => (int)$input->getArgument('id')]
                )
            );

            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $jobRepository = $this->entityManager->getRepository(Job::class);

        /* @var Job $job */
        $job = $jobRepository->find((int)$input->getArgument('id'));

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
        } catch (JobPreparationException $jobPreparationServiceException) {
            return self::RETURN_CODE_CANNOT_PREPARE_IN_WRONG_STATE;
        }

        return self::RETURN_CODE_OK;
    }
}
