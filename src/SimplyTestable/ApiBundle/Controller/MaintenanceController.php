<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Services\CommandService;

class MaintenanceController extends ApiController
{   
    public function enableBackupReadOnlyAction()
    {        
        return $this->executeCommand('SimplyTestable\ApiBundle\Command\Maintenance\EnableBackupReadOnlyCommand');
    }      
    
    
    public function enableReadOnlyAction()
    {        
        return $this->executeCommand('SimplyTestable\ApiBundle\Command\Maintenance\EnableReadOnlyCommand');
    }    
    
    public function disableReadOnlyAction() {
        return $this->executeCommand('SimplyTestable\ApiBundle\Command\Maintenance\DisableReadOnlyCommand');      
    }
    
    public function leaveReadOnlyAction() {
        $commands = array(
            'SimplyTestable\ApiBundle\Command\Maintenance\DisableReadOnlyCommand',
            'SimplyTestable\ApiBundle\Command\Job\EnqueuePrepareAllCommand',
            'SimplyTestable\ApiBundle\Command\Task\AssignSelectedCommand',
            'SimplyTestable\ApiBundle\Command\Task\EnqueueCancellationForAwaitingCancellationCommand'
        );
        
        $responseLines = array();
        
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
    
    private function executeCommand($commandClass, $inputArray = array()) {      
        $output = new \CoreSphere\ConsoleBundle\Output\StringOutput();
        $commandResponse =  $this->getCommandService()->execute(
                $commandClass,
                $inputArray,
                $output
        );
        
        $outputLines = explode("\n", trim($output->getBuffer()));
        
        return $this->sendResponse($outputLines, $commandResponse === 0 ? 200 : 500);        
    }

    
    /**
     *
     * @return CommandService
     */
    private function getCommandService() {
        return $this->container->get('simplytestable.services.commandService');
    }    
    
}
