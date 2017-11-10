<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Command\Job\EnqueuePrepareAllCommand;
use SimplyTestable\ApiBundle\Command\Maintenance\DisableReadOnlyCommand;
use SimplyTestable\ApiBundle\Command\Maintenance\EnableBackupReadOnlyCommand;
use SimplyTestable\ApiBundle\Command\Maintenance\EnableReadOnlyCommand;
use SimplyTestable\ApiBundle\Command\Task\EnqueueCancellationForAwaitingCancellationCommand;
use SimplyTestable\ApiBundle\Command\Tasks\RequeueQueuedForAssignmentCommand;
use SimplyTestable\ApiBundle\Command\Worker\TaskNotificationCommand;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function enableBackupReadOnlyAction()
    {
        return $this->executeCommand(
            $this->container->get(EnableBackupReadOnlyCommand::class)
        );
    }

    /**
     * @return JsonResponse
     */
    public function enableReadOnlyAction()
    {
        return $this->executeCommand(
            $this->container->get(EnableReadOnlyCommand::class)
        );
    }

    /**
     * @return JsonResponse
     */
    public function disableReadOnlyAction()
    {
        return $this->executeCommand(
            $this->container->get(DisableReadOnlyCommand::class)
        );
    }

    /**
     * @return Response
     */
    public function leaveReadOnlyAction()
    {
        $commands = [
            $this->container->get(DisableReadOnlyCommand::class),
            $this->container->get(EnqueuePrepareAllCommand::class),
            $this->container->get(RequeueQueuedForAssignmentCommand::class),
            $this->container->get(TaskNotificationCommand::class),
            $this->container->get(EnqueueCancellationForAwaitingCancellationCommand::class),
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
