<?php

namespace App\Command\Job;

use App\Repository\JobRepository;
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

    private $resqueQueueService;
    private $stateService;
    private $applicationStateService;
    private $jobRepository;

    public function __construct(
        ResqueQueueService $resqueQueueService,
        StateService $stateService,
        ApplicationStateService $applicationStateService,
        JobRepository $jobRepository,
        $name = null
    ) {
        parent::__construct($name);

        $this->resqueQueueService = $resqueQueueService;
        $this->stateService = $stateService;
        $this->applicationStateService = $applicationStateService;
        $this->jobRepository = $jobRepository;
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

        $jobIds = $this->jobRepository->getIdsByState($jobStartingState);
        $output->writeln(count($jobIds).' new jobs to prepare');

        foreach ($jobIds as $jobId) {
            $output->writeln('Enqueuing prepare for job '.$jobId);
            $this->resqueQueueService->enqueue(new PrepareJob(['id' => $jobId]));
        }

        return self::RETURN_CODE_OK;
    }
}
