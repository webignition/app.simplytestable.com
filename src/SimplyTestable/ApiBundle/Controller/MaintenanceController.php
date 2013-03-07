<?php

namespace SimplyTestable\ApiBundle\Controller;

//use SimplyTestable\ApiBundle\Services\CommandService;

class MaintenanceController extends ApiController
{   
    
//    public function enableReadOnlyAction()
//    {
//        return $this->executeCommand('SimplyTestable\ApiBundle\Command\MaintenanceEnableReadOnlyCommand');
//    }    
//    
//    public function disableReadOnlyAction() {
//        return $this->executeCommand('SimplyTestable\ApiBundle\Command\MaintenanceDisableReadOnlyCommand');      
//    }    
//    
//    public function taskPerformEnqueueAction() {
//        return $this->executeCommand('SimplyTestable\ApiBundle\Command\TaskPerformEnqueueCommand');         
//    }
//    
//    public function leaveReadOnlyAction() {
//        $commands = array(
//            'SimplyTestable\ApiBundle\Command\MaintenanceDisableReadOnlyCommand',
//            'SimplyTestable\ApiBundle\Command\TaskReportCompletionEnqueueCommand',
//            'SimplyTestable\ApiBundle\Command\TaskPerformEnqueueCommand'
//        );
//        
//        $responseLines = array();
//        
//        foreach ($commands as $command) {
//            $response = $this->executeCommand($command);
//            $rawResponseLines =  json_decode($response->getContent());
//            foreach ($rawResponseLines as $rawResponseLine) {
//                if (trim($rawResponseLine) != '') {
//                    $responseLines[] = trim($rawResponseLine);
//                }
//            }
//        }
//        
//        return $this->sendResponse($responseLines);
//    }
//    
//    
//    private function executeCommand($commandClass, $inputArray = array()) {      
//        $output = new \CoreSphere\ConsoleBundle\Output\StringOutput();
//        $commandResponse =  $this->getCommandService()->execute(
//                $commandClass,
//                $inputArray,
//                $output
//        );
//        
//        $outputLines = explode("\n", trim($output->getBuffer()));
//        
//        return $this->sendResponse($outputLines, $commandResponse === 0 ? 200 : 500);        
//    }
//
//    
//    /**
//     *
//     * @return CommandService
//     */
//    private function getCommandService() {
//        return $this->container->get('simplytestable.services.commandService');
//    }    
    
}
