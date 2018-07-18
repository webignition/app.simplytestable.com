<?php

namespace App\Command\Job;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Job\Job;
use App\Resque\Job\Job\PrepareJob;
use App\Services\ApplicationStateService;
use App\Services\Resque\QueueService as ResqueQueueService;
use App\Services\StateService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EnqueuePrepareAllCommand extends Command
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 1;

    /**
     * @var ResqueQueueService
     */
    private $resqueQueueService;

    /**
     * @var StateService
     */
    private $stateService;

    /**
     * @var ApplicationStateService
     */
    private $applicationStateService;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param ResqueQueueService $resqueQueueService
     * @param StateService $stateService
     * @param ApplicationStateService $applicationStateService
     * @param EntityManagerInterface $entityManager
     * @param string|null $name
     */
    public function __construct(
        ResqueQueueService $resqueQueueService,
        StateService $stateService,
        ApplicationStateService $applicationStateService,
        EntityManagerInterface $entityManager,
        $name = null
    ) {
        parent::__construct($name);

        $this->resqueQueueService = $resqueQueueService;
        $this->stateService = $stateService;
        $this->applicationStateService = $applicationStateService;
        $this->entityManager = $entityManager;
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
        if ($this->applicationStateService->isInReadOnlyMode()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $jobStartingState = $this->stateService->get(Job::STATE_STARTING);

        $jobRepository = $this->entityManager->getRepository(Job::class);
        $jobIds = $jobRepository->getIdsByState($jobStartingState);
        $output->writeln(count($jobIds).' new jobs to prepare');

        foreach ($jobIds as $jobId) {
            $output->writeln('Enqueuing prepare for job '.$jobId);
            $this->resqueQueueService->enqueue(new PrepareJob(['id' => $jobId]));
        }

        return self::RETURN_CODE_OK;
    }
}
