<?php
namespace SimplyTestable\ApiBundle\Command\Job;

use SimplyTestable\ApiBundle\Command\BaseCommand;

use SimplyTestable\ApiBundle\Exception\Services\JobPreparation\Exception as JobPreparationException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;

class PrepareCommand extends BaseCommand
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_CANNOT_PREPARE_IN_WRONG_STATE = 1;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 2;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:job:prepare')
            ->setDescription('Prepare a set of tasks for a given job')
            ->addArgument('id', InputArgument::REQUIRED, 'id of job to prepare')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $applicationStateService = $this->getContainer()->get('simplytestable.services.applicationstateservice');
        $resqueQueueService = $this->getContainer()->get('simplytestable.services.resque.queueservice');
        $resqueJobFactory = $this->getContainer()->get('simplytestable.services.resque.jobfactory');
        $logger = $this->getContainer()->get('logger');
        $jobService = $this->getContainer()->get('simplytestable.services.jobservice');
        $jobPreparationService = $this->getContainer()->get('simplytestable.services.jobpreparationservice');
        $crawlJobContainerService = $this->getContainer()->get('simplytestable.services.crawljobcontainerservice');

        if ($applicationStateService->isInMaintenanceReadOnlyState()) {
            $resqueQueueService->enqueue(
                $resqueJobFactory->create(
                    'job-prepare',
                    ['id' => (int)$input->getArgument('id')]
                )
            );

            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $logger->info(sprintf(
            'simplytestable:job:prepare running for job [%s]',
            $input->getArgument('id')
        ));

        $job = $jobService->getById((int)$input->getArgument('id'));

        foreach ($job->getRequestedTaskTypes() as $taskType) {
            /* @var TaskType $taskType */
            $taskTypeKey = strtolower(str_replace(' ', '-', $taskType->getName()));
            $taskTypeParameterDomainsToIgnoreKey = $taskTypeKey . '-domains-to-ignore';

            if ($this->getContainer()->hasParameter($taskTypeParameterDomainsToIgnoreKey)) {
                $taskTypeDomainsToIgnoreParameter = $this->getContainer()->getParameter(
                    $taskTypeParameterDomainsToIgnoreKey
                );

                $jobPreparationService->setPredefinedDomainsToIgnore($taskType, $taskTypeDomainsToIgnoreParameter);
            }
        }

        try {
            $jobPreparationService->prepare($job);

            if ($job->getTasks()->count()) {
                $resqueQueueService->enqueue(
                    $resqueJobFactory->create(
                        'tasks-notify'
                    )
                );
            } else {
                if ($crawlJobContainerService->hasForJob($job)) {
                    $crawlJob = $crawlJobContainerService->getForJob($job)->getCrawlJob();

                    $resqueQueueService->enqueue(
                        $resqueJobFactory->create(
                            'task-assign-collection',
                            ['ids' => $crawlJob->getTasks()->first()->getId()]
                        )
                    );
                }
            }

            $logger->info(sprintf(
                'simplytestable:job:prepare: queued up [%s] tasks covering [%s] urls and [%s] task types',
                $job->getTasks()->count(),
                $job->getUrlCount(),
                count($job->getRequestedTaskTypes())
            ));
        } catch (JobPreparationException $jobPreparationServiceException) {
            $logger->info(sprintf(
                'simplytestable:job:prepare: nothing to do, job has a state of [%s]',
                $job->getState()->getName()
            ));

            return self::RETURN_CODE_CANNOT_PREPARE_IN_WRONG_STATE;
        }

        return self::RETURN_CODE_OK;
    }
}
