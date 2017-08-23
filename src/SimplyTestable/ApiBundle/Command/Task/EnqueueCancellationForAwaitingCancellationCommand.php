<?php
namespace SimplyTestable\ApiBundle\Command\Task;

use SimplyTestable\ApiBundle\Command\BaseCommand;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EnqueueCancellationForAwaitingCancellationCommand extends BaseCommand
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 1;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:enqueue-cancellation-for-awaiting-cancellation')
            ->setDescription('Enqueue resque jobs for cancelling tasks that are awaiting cancellation')
            ->setHelp('Enqueue resque jobs for cancelling tasks that are awaiting cancellation');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $applicationStateService = $this->getContainer()->get('simplytestable.services.applicationstateservice');

        if ($applicationStateService->isInMaintenanceReadOnlyState()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $taskService = $this->getContainer()->get('simplytestable.services.taskservice');

        $taskIds = $taskService->getEntityRepository()->getIdsByState(
            $taskService->getAwaitingCancellationState()
        );

        $output->writeln(count($taskIds).' tasks to enqueue for cancellation');
        if (count($taskIds) === 0) {
            return self::RETURN_CODE_OK;
        }

        $output->writeln('Enqueuing for cancellation tasks '.  implode(',', $taskIds));

        $resqueQueueService = $this->getContainer()->get('simplytestable.services.resque.queueService');
        $resqueJobFactory = $this->getContainer()->get('simplytestable.services.resque.jobFactory');

        $resqueQueueService->enqueue(
            $resqueJobFactory->create(
                'task-cancel-collection',
                ['ids' => implode(',', $taskIds)]
            )
        );

        return self::RETURN_CODE_OK;
    }
}
