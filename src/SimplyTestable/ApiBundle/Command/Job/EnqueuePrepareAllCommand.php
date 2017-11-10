<?php
namespace SimplyTestable\ApiBundle\Command\Job;

use Doctrine\ORM\EntityManagerInterface;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Repository\JobRepository;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\Resque\JobFactory as ResqueJobFactory;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\ApiBundle\Services\StateService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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
     * @var ApplicationStateService
     */
    private $applicationStateService;

    /**
     * @var JobRepository
     */
    private $jobRepository;

    /**
     * @param ResqueQueueService $resqueQueueService
     * @param ResqueJobFactory $resqueJobFactory
     * @param StateService $stateService
     * @param ApplicationStateService $applicationStateService
     * @param EntityManagerInterface $entityManager
     * @param string|null $name
     */
    public function __construct(
        ResqueQueueService $resqueQueueService,
        ResqueJobFactory $resqueJobFactory,
        StateService $stateService,
        ApplicationStateService $applicationStateService,
        EntityManagerInterface $entityManager,
        $name = null
    ) {
        parent::__construct($name);

        $this->resqueQueueService = $resqueQueueService;
        $this->resqueJobFactory = $resqueJobFactory;
        $this->stateService = $stateService;
        $this->applicationStateService = $applicationStateService;
        $this->jobRepository = $entityManager->getRepository(Job::class);
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

        $jobStartingState = $this->stateService->get(JobService::STARTING_STATE);

        $jobIds = $this->jobRepository->getIdsByState($jobStartingState);
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
