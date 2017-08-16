<?php
namespace SimplyTestable\ApiBundle\Command\Job;

use SimplyTestable\ApiBundle\Command\BaseCommand;

use SimplyTestable\ApiBundle\Repository\JobRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Services\JobService;

class EnqueuePrepareAllCommand extends BaseCommand
{
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 1;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:job:enqueue-prepare-all')
            ->setDescription('Enqueue all new jobs to be prepared')
            ->addArgument('http-fixture-path', InputArgument::OPTIONAL, 'path to HTTP fixture data when testing')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $resqueQueueService = $this->getContainer()->get('simplytestable.services.resque.queueservice');
        $resqueJobFactory = $this->getContainer()->get('simplytestable.services.resque.jobfactoryservice');
        $stateService = $this->getContainer()->get('simplytestable.services.stateservice');
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        /* @var JobRepository $jobRepository */
        $jobRepository = $entityManager->getRepository(Job::class);

        $jobStartingState = $stateService->fetch(JobService::STARTING_STATE);

        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $jobIds = $jobRepository->getIdsByState($jobStartingState);
        $output->writeln(count($jobIds).' new jobs to prepare');

        foreach ($jobIds as $jobId) {
            $output->writeln('Enqueuing prepare for job '.$jobId);

            $resqueQueueService->enqueue(
                $resqueJobFactory->create(
                    'job-prepare',
                    ['id' => $jobId]
                )
            );
        }

        return 0;
    }
}
