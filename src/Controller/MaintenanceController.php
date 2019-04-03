<?php

namespace App\Controller;

use App\Command\Job\EnqueuePrepareAllCommand;
use App\Command\Maintenance\DisableReadOnlyCommand;
use App\Command\Maintenance\EnableBackupReadOnlyCommand;
use App\Command\Maintenance\EnableReadOnlyCommand;
use App\Command\Task\EnqueueCancellationForAwaitingCancellationCommand;
use App\Command\Tasks\RequeueQueuedForAssignmentCommand;
use App\Command\Worker\TaskNotificationCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceController
{
    /**
     * @param EnableBackupReadOnlyCommand $enableBackupReadOnlyCommand
     *
     * @return JsonResponse
     */
    public function enableBackupReadOnlyAction(EnableBackupReadOnlyCommand $enableBackupReadOnlyCommand)
    {
        return $this->executeCommand($enableBackupReadOnlyCommand);
    }

    /**
     * @param EnableReadOnlyCommand $enableReadOnlyCommand
     *
     * @return JsonResponse
     */
    public function enableReadOnlyAction(EnableReadOnlyCommand $enableReadOnlyCommand)
    {
        return $this->executeCommand($enableReadOnlyCommand);
    }

    /**
     * @param DisableReadOnlyCommand $disableReadOnlyCommand
     *
     * @return JsonResponse
     */
    public function disableReadOnlyAction(DisableReadOnlyCommand $disableReadOnlyCommand)
    {
        return $this->executeCommand($disableReadOnlyCommand);
    }

    /**
     * @param DisableReadOnlyCommand $disableReadOnlyCommand
     * @param EnqueuePrepareAllCommand $enqueuePrepareAllCommand
     * @param RequeueQueuedForAssignmentCommand $requeueQueuedForAssignmentCommand
     * @param TaskNotificationCommand $taskNotificationCommand
     *
     * @return Response
     */
    public function leaveReadOnlyAction(
        DisableReadOnlyCommand $disableReadOnlyCommand,
        EnqueuePrepareAllCommand $enqueuePrepareAllCommand,
        RequeueQueuedForAssignmentCommand $requeueQueuedForAssignmentCommand,
        TaskNotificationCommand $taskNotificationCommand
    ) {
        $commands = [
            $disableReadOnlyCommand,
            $enqueuePrepareAllCommand,
            $requeueQueuedForAssignmentCommand,
            $taskNotificationCommand,
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

        return new JsonResponse($responseLines);
    }

    /**
     * @param Command $command
     *
     * @return JsonResponse
     */
    private function executeCommand(Command $command)
    {
        $output = new BufferedOutput();
        $commandResponse = $command->run(new ArrayInput([]), $output);

        $outputLines = explode("\n", trim($output->fetch()));

        return new JsonResponse($outputLines, $commandResponse === 0 ? 200 : 500);
    }
}
