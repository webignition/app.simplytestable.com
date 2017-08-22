<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Command\Job\EnqueuePrepareAllCommand;
use SimplyTestable\ApiBundle\Command\Maintenance\DisableReadOnlyCommand;
use SimplyTestable\ApiBundle\Command\Maintenance\EnableBackupReadOnlyCommand;
use SimplyTestable\ApiBundle\Command\Maintenance\EnableReadOnlyCommand;
use SimplyTestable\ApiBundle\Command\Task\EnqueueCancellationForAwaitingCancellationCommand;
use SimplyTestable\ApiBundle\Command\Tasks\RequeueQueuedForAssignmentCommand;
use SimplyTestable\ApiBundle\Command\Worker\TaskNotificationCommand;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceController extends ApiController
{
    /**
     * @return Response
     */
    public function enableBackupReadOnlyAction()
    {
        return $this->executeCommand(EnableBackupReadOnlyCommand::class);
    }

    /**
     * @return Response
     */
    public function enableReadOnlyAction()
    {
        return $this->executeCommand(EnableReadOnlyCommand::class);
    }

    /**
     * @return Response
     */
    public function disableReadOnlyAction()
    {
        return $this->executeCommand(DisableReadOnlyCommand::class);
    }

    /**
     * @return Response
     */
    public function leaveReadOnlyAction()
    {
        $commands = [
            DisableReadOnlyCommand::class,
            EnqueuePrepareAllCommand::class,
            RequeueQueuedForAssignmentCommand::class,
            TaskNotificationCommand::class,
            EnqueueCancellationForAwaitingCancellationCommand::class,
        ];

        $responseLines = [];

        foreach ($commands as $command) {
            $response = $this->executeCommand($command);
            $rawResponseLines =  json_decode($response->getContent());
            foreach ($rawResponseLines as $rawResponseLine) {
                if (trim($rawResponseLine) != '') {
                    $responseLines[] = trim($rawResponseLine);
                }
            }
        }

        return $this->sendResponse($responseLines);
    }

    /**
     * @param string $commandClass
     *
     * @return JsonResponse
     */
    private function executeCommand($commandClass)
    {
        /* @var ContainerAwareCommand $command */
        $command = new $commandClass;
        $command->setContainer($this->container);

        $output = new BufferedOutput();
        $commandResponse = $command->run(new ArrayInput([]), $output);

        $outputLines = explode("\n", trim($output->fetch()));

        return new JsonResponse($outputLines, $commandResponse === 0 ? 200 : 500);
    }
}
