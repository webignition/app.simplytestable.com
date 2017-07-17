<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Command\Job\EnqueuePrepareAllCommand;
use SimplyTestable\ApiBundle\Command\Maintenance\DisableReadOnlyCommand;
use SimplyTestable\ApiBundle\Command\Maintenance\EnableBackupReadOnlyCommand;
use SimplyTestable\ApiBundle\Command\Maintenance\EnableReadOnlyCommand;
use SimplyTestable\ApiBundle\Command\Task\Assign\SelectedCommand as AssignSelectedCommand;
use SimplyTestable\ApiBundle\Command\Task\EnqueueCancellationForAwaitingCancellationCommand;
use Symfony\Component\Console\Output\BufferedOutput;
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
            AssignSelectedCommand::class,
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
     * @param array $inputArray
     *
     * @return Response
     */
    private function executeCommand($commandClass, $inputArray = [])
    {
        $output = new BufferedOutput();
        $commandService = $this->container->get('simplytestable.services.commandService');

        $commandResponse =  $commandService->execute(
            $commandClass,
            $inputArray,
            $output
        );

        $outputLines = explode("\n", trim($output->fetch()));

        return $this->sendResponse($outputLines, $commandResponse === 0 ? 200 : 500);
    }
}
