<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Services\CommandService;

class MaintenanceController extends ApiController
{   
    
    public function enableReadOnlyAction()
    {        
        return $this->executeCommand('SimplyTestable\ApiBundle\Command\Maintenance\MaintenanceEnableReadOnlyCommand');
    }    
    
    public function disableReadOnlyAction() {
        return $this->executeCommand('SimplyTestable\ApiBundle\Command\Maintenance\MaintenanceDisableReadOnlyCommand');      
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
