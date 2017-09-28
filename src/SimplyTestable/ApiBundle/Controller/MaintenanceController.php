<?php

namespace SimplyTestable\ApiBundle\Controller;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceController extends ApiController
{
    /**
     * @return JsonResponse
     */
    public function enableBackupReadOnlyAction()
    {
        return $this->executeCommand(
            $this->container->get('simplytestable.command.maintenance.enablebackupreadonly')
        );
    }

    /**
     * @return JsonResponse
     */
    public function enableReadOnlyAction()
    {
        return $this->executeCommand(
            $this->container->get('simplytestable.command.maintenance.enablereadonly')
        );
    }

    /**
     * @return JsonResponse
     */
    public function disableReadOnlyAction()
    {
        return $this->executeCommand(
            $this->container->get('simplytestable.command.maintenance.disablereadonly')
        );
    }

    /**
     * @return Response
     */
    public function leaveReadOnlyAction()
    {
        $commands = [
            $this->container->get('simplytestable.command.maintenance.disablereadonly'),
            $this->container->get('simplytestable.command.job.enqueueprepareall'),
            $this->container->get('simplytestable.command.tasks.requeuequeuedforassignment'),
            $this->container->get('simplytestable.command.worker.tasknotification'),
            $this->container->get('simplytestable.command.task.enqueuecancellationforawaitingcancellationcommand'),
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
