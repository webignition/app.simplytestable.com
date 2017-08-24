<?php
namespace SimplyTestable\ApiBundle\Command\Job;

use SimplyTestable\ApiBundle\Exception\Services\Job\WebsiteResolutionException;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\Job\WebsiteResolutionService;
use SimplyTestable\ApiBundle\Services\JobPreparationService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\Resque\JobFactory as ResqueJobFactory;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;

class ResolveWebsiteCommand extends Command
{
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
     * @var ResqueJobFactory
     */
    private $resqueJobFactory;

    /**
     * @var JobService
     */
    private $jobService;

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
     * @param ApplicationStateService $applicationStateService
     * @param ResqueQueueService $resqueQueueService
     * @param ResqueJobFactory $resqueJobFactory
     * @param JobService $jobService
     * @param WebsiteResolutionService $websiteResolutionService
     * @param JobPreparationService $jobPreparationService
     * @param array $predefinedDomainsToIgnore
     * @param string|null $name
     */
    public function __construct(
        ApplicationStateService $applicationStateService,
        ResqueQueueService $resqueQueueService,
        ResqueJobFactory $resqueJobFactory,
        JobService $jobService,
        WebsiteResolutionService $websiteResolutionService,
        JobPreparationService $jobPreparationService,
        $predefinedDomainsToIgnore,
        $name = null
    ) {
        parent::__construct($name);

        $this->applicationStateService = $applicationStateService;
        $this->resqueQueueService = $resqueQueueService;
        $this->resqueJobFactory = $resqueJobFactory;
        $this->jobService = $jobService;
        $this->websiteResolutionService = $websiteResolutionService;
        $this->jobPreparationService = $jobPreparationService;
        $this->predefinedDomainsToIgnore = $predefinedDomainsToIgnore;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:job:resolve')
            ->setDescription('Resolve a job\'s canonical url to be sure where we are starting off')
            ->addArgument('id', InputArgument::REQUIRED, 'id of job to process')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->applicationStateService->isInMaintenanceReadOnlyState()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $job = $this->jobService->getById((int)$input->getArgument('id'));

        try {
            $this->websiteResolutionService->resolve($job);
        } catch (WebsiteResolutionException $websiteResolutionException) {
            if ($websiteResolutionException->isJobInWrongStateException()) {
                return self::RETURN_CODE_CANNOT_RESOLVE_IN_WRONG_STATE;
            }
        }

        if (JobService::REJECTED_STATE === $job->getState()->getName()) {
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

            $this->resqueQueueService->enqueue(
                $this->resqueJobFactory->create(
                    'task-assign-collection',
                    ['ids' => implode(',', $job->getTaskIds())]
                )
            );
        } else {
            $this->resqueQueueService->enqueue(
                $this->resqueJobFactory->create(
                    'job-prepare',
                    ['id' => $job->getId()]
                )
            );
        }

        return self::RETURN_CODE_OK;
    }
}
