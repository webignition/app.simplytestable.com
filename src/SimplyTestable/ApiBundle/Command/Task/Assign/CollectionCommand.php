<?php
namespace SimplyTestable\ApiBundle\Command\Task\Assign;

use SimplyTestable\ApiBundle\Command\BaseCommand;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Services\JobService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CollectionCommand extends BaseCommand
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_FAILED_NO_WORKERS = 1;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = -1;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:assigncollection')
            ->setDescription('Assign a collection of tasks to workers')
            ->addArgument('ids', InputArgument::REQUIRED, 'ids of tasks to assign')
            ->addArgument('worker', InputArgument::OPTIONAL, 'hostname of worker to which to assign tasks')
            ->setHelp('Assign a collection of tasks to workers');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $applicationStateService = $this->getContainer()->get('simplytestable.services.applicationstateservice');

        if ($applicationStateService->isInMaintenanceReadOnlyState()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $taskIds = explode(',', $input->getArgument('ids'));

        if (empty($taskIds)) {
            return self::RETURN_CODE_OK;
        }

        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $taskRepository = $entityManager->getRepository(Task::class);

        $tasks = $taskRepository->findBy([
            'id' => $taskIds,
        ]);

        $taskPreprocessorFactory = $this->getContainer()->get('simplytestable.services.taskPreprocessorServiceFactory');

        foreach ($tasks as $taskIndex => $task) {
            if ($taskPreprocessorFactory->hasPreprocessor($task)) {
                $preProcessorResponse = false;

                try {
                    $preProcessorResponse = $taskPreprocessorFactory->getPreprocessor($task)->process($task);
                } catch (\Exception $e) {
                }

                if ($preProcessorResponse === true) {
                    unset($tasks[$taskIndex]);
                }
            }
        }

        if (count($tasks) === 0) {
            return self::RETURN_CODE_OK;
        }

        $workerService = $this->getContainer()->get('simplytestable.services.workerservice');
        $activeWorkers = $workerService->getActiveCollection();
        $workers = [];

        if (is_null($input->getArgument('worker'))) {
            $workers = $activeWorkers;
        } else {
            $selectedWorker = trim($input->getArgument('worker'));

            foreach ($activeWorkers as $activeWorker) {
                if ($activeWorker->getHostname() == $selectedWorker) {
                    $workers[] = $activeWorker;
                }
            }
        }

        $resqueQueueService = $this->getContainer()->get('simplytestable.services.resque.queueService');
        $resqueJobFactoryService = $this->getContainer()->get('simplytestable.services.resque.jobFactory');

        if (count($workers) === 0) {
            $this->getLogger()->error("TaskAssignCollectionCommand::execute: Cannot assign, no workers.");

            $resqueQueueService->enqueue(
                $resqueJobFactoryService->create(
                    'task-assign-collection',
                    ['ids' => implode(',', $taskIds)]
                )
            );

            return self::RETURN_CODE_FAILED_NO_WORKERS;
        }

        $stateService = $this->getContainer()->get('simplytestable.services.stateservice');
        $jobInProgressState = $stateService->fetch(JobService::IN_PROGRESS_STATE);

        $workerTaskAssignmentService = $this->getContainer()->get(
            'simplytestable.services.workertaskassignmentservice'
        );

        $response = $workerTaskAssignmentService->assignCollection($tasks, $workers);
        if ($response === 0) {
            /* @var Job $job */
            $job = $tasks[0]->getJob();
            if ($job->getState()->getName() == 'job-queued') {
                $job->setState($jobInProgressState);
                $entityManager->persist($job);
            }

            $entityManager->flush();
        } else {
            $resqueQueueService->enqueue(
                $resqueJobFactoryService->create(
                    'task-assign-collection',
                    ['ids' => implode(',', $taskIds)]
                )
            );
        }

        return $response;
    }
}
