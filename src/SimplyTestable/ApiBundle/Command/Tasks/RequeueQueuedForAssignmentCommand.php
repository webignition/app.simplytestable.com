<?php
namespace SimplyTestable\ApiBundle\Command\Tasks;

use SimplyTestable\ApiBundle\Command\BaseCommand;

use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Services\TaskService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RequeueQueuedForAssignmentCommand extends BaseCommand
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 1;

    const NAME = 'simplytestable:tasks:requeue-queued-for-assignment';
    const DESCRIPTION = 'Change the state of all "queued-for-assignment" tasks to "queued"';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription(self::DESCRIPTION)
            ->setHelp(self::DESCRIPTION);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $applicationStateService = $this->getContainer()->get('simplytestable.services.applicationstateservice');

        $isInMaintenanceReadOnlyState = $applicationStateService->isInMaintenanceReadOnlyState();
        $isInMaintenanceBackupReadOnlyState = $applicationStateService->isInMaintenanceBackupReadOnlyState();

        $isInReadOnlyState = $isInMaintenanceReadOnlyState || $isInMaintenanceBackupReadOnlyState;

        if ($isInReadOnlyState) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $stateService = $this->getContainer()->get('simplytestable.services.stateservice');
        $taskService = $this->getContainer()->get('simplytestable.services.taskservice');
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        $taskQueuedForAssignmentState = $stateService->fetch(TaskService::QUEUED_FOR_ASSIGNMENT_STATE);

        /* @var Task[] $tasks */
        $tasks = $taskService->getEntityRepository()->findBy([
            'state' => $taskQueuedForAssignmentState,
        ]);

        if (empty($tasks)) {
            return self::RETURN_CODE_OK;
        }

        $taskQueuedState = $stateService->fetch(TaskService::QUEUED_STATE);

        foreach ($tasks as $task) {
            $task->setState($taskQueuedState);
            $entityManager->persist($task);
        }

        $entityManager->flush();

        return self::RETURN_CODE_OK;
    }
}
