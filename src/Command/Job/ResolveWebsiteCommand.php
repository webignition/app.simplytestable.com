<?php
namespace App\Command\Job;

use App\Repository\JobRepository;
use App\Services\StateService;
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
use App\Entity\Task\TaskType;

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
     * @var StateService
     */
    private $stateService;

    private $jobRepository;


    public function __construct(
        ApplicationStateService $applicationStateService,
        ResqueQueueService $resqueQueueService,
        WebsiteResolutionService $websiteResolutionService,
        JobPreparationService $jobPreparationService,
        StateService $stateService,
        JobRepository $jobRepository,
        array $predefinedDomainsToIgnore,
        $name = null
    ) {
        parent::__construct($name);

        $this->applicationStateService = $applicationStateService;
        $this->resqueQueueService = $resqueQueueService;
        $this->websiteResolutionService = $websiteResolutionService;
        $this->jobPreparationService = $jobPreparationService;
        $this->predefinedDomainsToIgnore = $predefinedDomainsToIgnore;
        $this->stateService = $stateService;
        $this->jobRepository = $jobRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Resolve a job\'s canonical url to be sure where we are starting off')
            ->addArgument('id', InputArgument::REQUIRED, 'id(s) of job(s) to process')
            ->addOption('reset-state');
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

        $jobs = $this->getJobs($input);
        $jobCount = count($jobs);
        $shouldResetState = $input->getOption('reset-state');

        $results = [];

        foreach ($jobs as $jobIndex => $job) {
            $jobNumber = $jobIndex + 1;

            $output->writeln(sprintf(
                '<info>Resolving job %s of %s</info> <comment>%s</comment>',
                $jobNumber,
                $jobCount,
                $job->getId()
            ));

            $results[] = $this->resolveJob($job, $shouldResetState);
        }

        if (count($results) === 1) {
            return $results[0];
        }

        return self::RETURN_CODE_OK;
    }

    private function resolveJob(Job $job, bool $shouldResetState): int
    {
        if ($shouldResetState && Job::STATE_STARTING != $job->getState()) {
            $job->setState($this->stateService->get(Job::STATE_STARTING));
        }

        try {
            $this->websiteResolutionService->resolve($job);
        } catch (WebsiteResolutionException $websiteResolutionException) {
            if ($websiteResolutionException->isJobInWrongStateException()) {
                return self::RETURN_CODE_CANNOT_RESOLVE_IN_WRONG_STATE;
            }
        }

        if (Job::STATE_REJECTED === (string) $job->getState()) {
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

    /**
     * @param InputInterface $input
     *
     * @return Job[]
     */
    private function getJobs(InputInterface $input)
    {
        $identifier = $input->getArgument('id');

        if (ctype_digit($identifier)) {
            return [
                $this->jobRepository->find((int)$input->getArgument('id')),
            ];
        }

        if (preg_match('/([0-9]+,?)+/', $identifier)) {
            $jobIds = explode(',', $identifier);

            return $this->jobRepository->findBy([
                'id' => $jobIds,
            ]);
        }

        return [];
    }
}
