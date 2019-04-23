<?php

namespace App\Command\Job;

use App\Repository\JobRepository;
use Psr\Log\LoggerInterface;
use App\Entity\Job\Job;
use App\Exception\Services\JobPreparation\Exception as JobPreparationException;
use App\Resque\Job\Job\PrepareJob;
use App\Resque\Job\Task\AssignCollectionJob;
use App\Resque\Job\Worker\Tasks\NotifyJob;
use App\Services\ApplicationStateService;
use App\Services\CrawlJobContainerService;
use App\Services\JobPreparationService;
use App\Services\Resque\QueueService as ResqueQueueService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use App\Entity\Task\TaskType;

class PrepareCommand extends Command
{
    const NAME = 'simplytestable:job:prepare';
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_CANNOT_PREPARE_IN_WRONG_STATE = 1;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 2;

    private $applicationStateService;
    private $resqueQueueService;
    private $jobPreparationService;
    private $crawlJobContainerService;
    private $logger;
    private $jobRepository;

    /**
     * @var array
     */
    private $predefinedDomainsToIgnore;

    public function __construct(
        ApplicationStateService $applicationStateService,
        ResqueQueueService $resqueQueueService,
        JobPreparationService $jobPreparationService,
        CrawlJobContainerService $crawlJobContainerService,
        LoggerInterface $logger,
        JobRepository $jobRepository,
        array $predefinedDomainsToIgnore,
        $name = null
    ) {
        parent::__construct($name);

        $this->applicationStateService = $applicationStateService;
        $this->resqueQueueService = $resqueQueueService;
        $this->jobPreparationService = $jobPreparationService;
        $this->crawlJobContainerService = $crawlJobContainerService;
        $this->logger = $logger;
        $this->predefinedDomainsToIgnore = $predefinedDomainsToIgnore;
        $this->jobRepository = $jobRepository;
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
            $this->resqueQueueService->enqueue(new PrepareJob(['id' => (int)$input->getArgument('id')]));

            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $this->logger->info(sprintf(
            'simplytestable:job:prepare running for job [%s]',
            $input->getArgument('id')
        ));

        /* @var Job $job */
        $job = $this->jobRepository->find((int)$input->getArgument('id'));

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
                $this->resqueQueueService->enqueue(new NotifyJob());
            } else {
                if ($this->crawlJobContainerService->hasForJob($job)) {
                    $crawlJob = $this->crawlJobContainerService->getForJob($job)->getCrawlJob();

                    $this->resqueQueueService->enqueue(new AssignCollectionJob([
                        'ids' => $crawlJob->getTasks()->first()->getId()
                    ]));
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
