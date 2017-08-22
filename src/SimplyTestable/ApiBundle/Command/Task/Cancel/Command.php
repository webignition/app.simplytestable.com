<?php
namespace SimplyTestable\ApiBundle\Command\Task\Cancel;

use SimplyTestable\ApiBundle\Command\BaseCommand;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use SimplyTestable\ApiBundle\Entity\Task\Task;

class Command extends BaseCommand
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_TASK_DOES_NOT_EXIST = -1;
    const RETURN_CODE_FAILED_DUE_TO_WRONG_STATE = -2;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = -3;

    protected function configure()
    {
        $this
            ->setName('simplytestable:task:cancel')
            ->setDescription('Cancel a task')
            ->addArgument('id', InputArgument::REQUIRED, 'id of task to cancel')
            ->setHelp(<<<EOF
Cancel a task
EOF
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $applicationStateService = $this->getContainer()->get('simplytestable.services.applicationstateservice');

        if ($applicationStateService->isInMaintenanceReadOnlyState()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        /* @var $task Task*/
        $task = $this->getTaskService()->getById((int)$input->getArgument('id'));
        if (is_null($task)) {
            $output->writeln('Unable to cancel, task '.$input->getArgument('id').' does not exist');
            return self::RETURN_CODE_TASK_DOES_NOT_EXIST;
        }

        $cancellationResult = $this->getWorkerTaskCancellationService()->cancel($task);

        if ($cancellationResult === 200) {
            return self::RETURN_CODE_OK;
        }

        if ($cancellationResult === -1) {
            $output->writeln('Cancellation request failed, task is in wrong state (currently:'.$task->getState().')');
            return self::RETURN_CODE_FAILED_DUE_TO_WRONG_STATE;
        }

        $task->setState($this->getTaskService()->getAwaitingCancellationState());
        $this->getTaskService()->getManager()->persist($task);
        $this->getTaskService()->getManager()->flush();

        if ($this->isHttpStatusCode($cancellationResult)) {
            $output->writeln('Cancellation request failed, HTTP response '.$cancellationResult);
        } else {
            $output->writeln('Cancellation request failed, CURL error '.$cancellationResult);
        }

        return $cancellationResult;
    }


    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\TaskService
     */
    private function getTaskService() {
        return $this->getContainer()->get('simplytestable.services.taskservice');
    }


    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\WorkerTaskCancellationService
     */
    private function getWorkerTaskCancellationService() {
        return $this->getContainer()->get('simplytestable.services.workertaskcancellationservice');
    }
}