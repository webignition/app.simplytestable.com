<?php
namespace App\Command\Job;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Job\Job;
use App\Exception\Services\Job\WebsiteResolutionException;
use App\Resque\Job\Job\PrepareJob;
use App\Resque\Job\Task\AssignCollectionJob;
use App\Services\ApplicationStateService;
use App\Services\Job\WebsiteResolutionService;
use App\Services\JobPreparationService;
use App\Services\JobTypeService;
use App\Services\Resque\QueueService as ResqueQueueService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\Task\Type\Type as TaskType;

class ResolveWebsiteCommand extends Command
{
    const NAME = 'simplytestable:job:resolve';

    const RETURN_CODE_OK = 0;
    const RETURN_CODE_CANNOT_RESOLVE_IN_WRONG_STATE = 1;
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
     * @var WebsiteResolutionService
     */
    private $websiteResolutionService;

    /**
     * @var JobPreparationService
     */
    private $jobPreparationService;

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
     * @param WebsiteResolutionService $websiteResolutionService
     * @param JobPreparationService $jobPreparationService
     * @param EntityManagerInterface $entityManager
     * @param array $predefinedDomainsToIgnore
     * @param string|null $name
     */
    public function __construct(
        ApplicationStateService $applicationStateService,
        ResqueQueueService $resqueQueueService,
        WebsiteResolutionService $websiteResolutionService,
        JobPreparationService $jobPreparationService,
        EntityManagerInterface $entityManager,
        $predefinedDomainsToIgnore,
        $name = null
    ) {
        parent::__construct($name);

        $this->applicationStateService = $applicationStateService;
        $this->resqueQueueService = $resqueQueueService;
        $this->websiteResolutionService = $websiteResolutionService;
        $this->jobPreparationService = $jobPreparationService;
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
            ->setDescription('Resolve a job\'s canonical url to be sure where we are starting off')
            ->addArgument('id', InputArgument::REQUIRED, 'id of job to process')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->applicationStateService->isInReadOnlyMode()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $jobRepository = $this->entityManager->getRepository(Job::class);

        /* @var Job $job */
        $job = $jobRepository->find((int)$input->getArgument('id'));

        try {
            $this->websiteResolutionService->resolve($job);
        } catch (WebsiteResolutionException $websiteResolutionException) {
            if ($websiteResolutionException->isJobInWrongStateException()) {
                return self::RETURN_CODE_CANNOT_RESOLVE_IN_WRONG_STATE;
            }
        }

        if (Job::STATE_REJECTED === $job->getState()->getName()) {
            return self::RETURN_CODE_OK;
        }

        if (JobTypeService::SINGLE_URL_NAME === $job->getType()->getName()) {
            foreach ($job->getRequestedTaskTypes() as $taskType) {
                /* @var TaskType $taskType */
                $taskTypeKey = strtolower(str_replace(' ', '-', $taskType->getName()));

                if (isset($this->predefinedDomainsToIgnore[$taskTypeKey])) {
                    $predefinedDomainsToIgnore = $this->predefinedDomainsToIgnore[$taskTypeKey];
                    $this->jobPreparationService->setPredefinedDomainsToIgnore($taskType, $predefinedDomainsToIgnore);
                }
            }

            $this->jobPreparationService->prepare($job);
            $this->resqueQueueService->enqueue(new AssignCollectionJob(['ids' => implode(',', $job->getTaskIds())]));
        } else {
            $this->resqueQueueService->enqueue(new PrepareJob(['id' => $job->getId()]));
        }

        return self::RETURN_CODE_OK;
    }
}
