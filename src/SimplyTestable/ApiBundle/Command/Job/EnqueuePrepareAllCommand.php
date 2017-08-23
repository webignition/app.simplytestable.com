<?php
namespace SimplyTestable\ApiBundle\Command\Job;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Repository\JobRepository;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\Resque\JobFactoryService as ResqueJobFactory;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\ApiBundle\Services\StateService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Services\JobService;

class EnqueuePrepareAllCommand extends Command
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 1;

    /**
     * @var ResqueQueueService
     */
    private $resqueQueueService;

    /**
     * @var ResqueJobFactory
     */
    private $resqueJobFactory;

    /**
     * @var StateService
     */
    private $stateService;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ApplicationStateService
     */
    private $applicationStateService;

    /**
     * @param ResqueQueueService $resqueQueueService
     * @param ResqueJobFactory $resqueJobFactory
     * @param StateService $stateService
     * @param EntityManager $entityManager
     * @param ApplicationStateService $applicationStateService
     * @param string|null $name
     */
    public function __construct(
        ResqueQueueService $resqueQueueService,
        ResqueJobFactory $resqueJobFactory,
        StateService $stateService,
        EntityManager $entityManager,
        ApplicationStateService $applicationStateService,
        $name = null
    ) {
        parent::__construct($name);

        $this->resqueQueueService = $resqueQueueService;
        $this->resqueJobFactory = $resqueJobFactory;
        $this->stateService = $stateService;
        $this->entityManager = $entityManager;
        $this->applicationStateService = $applicationStateService;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:job:enqueue-prepare-all')
            ->setDescription('Enqueue all new jobs to be prepared')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->applicationStateService->isInMaintenanceReadOnlyState()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        /* @var JobRepository $jobRepository */
        $jobRepository = $this->entityManager->getRepository(Job::class);
        $jobStartingState = $this->stateService->fetch(JobService::STARTING_STATE);

        $jobIds = $jobRepository->getIdsByState($jobStartingState);
        $output->writeln(count($jobIds).' new jobs to prepare');

        foreach ($jobIds as $jobId) {
            $output->writeln('Enqueuing prepare for job '.$jobId);

            $this->resqueQueueService->enqueue(
                $this->resqueJobFactory->create(
                    'job-prepare',
                    ['id' => $jobId]
                )
            );
        }

        return self::RETURN_CODE_OK;
    }
}
