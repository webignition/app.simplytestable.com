<?php
namespace SimplyTestable\ApiBundle\Command\Task\Assign;

use SimplyTestable\ApiBundle\Command\BaseCommand;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Services\JobService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SimplyTestable\ApiBundle\Entity\Task\Task;

class SelectedCommand extends BaseCommand
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
            ->setName('simplytestable:task:assign-selected')
            ->setDescription('Assign to workers tasks selected for assignment')
            ->setHelp('Assign to workers all tasks selected for assignment');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $taskService = $this->getContainer()->get('simplytestable.services.taskservice');

        $taskIds = $taskService->getEntityRepository()->getIdsByState($taskService->getQueuedForAssignmentState());
        $output->writeln(count($taskIds).' tasks queued for assignment');
        if (count($taskIds) === 0) {
            return self::RETURN_CODE_OK;
        }

        $output->writeln('Attempting to assign tasks '.  implode(',', $taskIds));

        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $taskRepository = $entityManager->getRepository(Task::class);

        $tasks = $taskRepository->findBy([
            'id' => $taskIds,
        ]);

        foreach ($tasks as $taskIndex => $task) {
            /* @var $task Task */
            $output->writeln('Handling task: ' . $task->getId() . '[' . $task->getType()->getName() . ']');

            $taskPreprocessorFactory = $this->getContainer()->get(
                'simplytestable.services.TaskPreProcessorServiceFactory'
            );

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
        $workers = $workerService->getActiveCollection();

        if (count($workers) === 0) {
            $this->getLogger()->error("TaskAssignSelectedCommand::execute: Cannot assign, no workers.");
            return self::RETURN_CODE_FAILED_NO_WORKERS;
        }

        $workerTaskAssignmentService = $this->getContainer()->get(
            'simplytestable.services.workertaskassignmentservice'
        );

        $response = $workerTaskAssignmentService->assignCollection($tasks, $workers);
        if ($response === 0) {
            $output->writeln('ok');

            $startedTasks = array();
            foreach ($tasks as $task) {
                $equivalentTasks = $taskService->getEquivalentTasks(
                    $task->getUrl(),
                    $task->getType(),
                    $task->getParametersHash(),
                    [
                        $taskService->getQueuedForAssignmentState(),
                        $taskService->getQueuedState()
                    ]
                );

                foreach ($equivalentTasks as $equivalentTask) {
                    $taskService->setStarted(
                        $equivalentTask,
                        $task->getWorker(),
                        $task->getRemoteId()
                    );

                    $taskService->persistAndFlush($equivalentTask);
                }

                $startedTasks = array_merge($startedTasks, $equivalentTasks, array($task));
            }

            $stateService = $this->getContainer()->get('simplytestable.services.stateservice');
            $jobInProgressState = $stateService->fetch(JobService::IN_PROGRESS_STATE);

            foreach ($startedTasks as $startedTask) {
                /* @var Job $job */
                $job = $startedTask->getJob();

                if ($job->getState()->getName() == 'job-queued') {
                    $job->setState($jobInProgressState);
                    $entityManager->persist($job);
                    $entityManager->flush();
                }
            }

        } else {
            $output->writeln('Failed to assign task collection, response '.$response);
        }

        return $response;
    }
}
