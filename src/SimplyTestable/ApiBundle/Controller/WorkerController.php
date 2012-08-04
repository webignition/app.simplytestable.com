<?php

namespace SimplyTestable\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use SimplyTestable\ApiBundle\Services\WorkerService;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputDefinition;

class WorkerController extends ApiController
{    
    public function __construct() {
        $this->setInputDefinitions(array(
            'activateAction' => new InputDefinition(array(
                new InputArgument('hostname', InputArgument::REQUIRED, 'Hostname of the worker to be activated'),
                new InputArgument('token', InputArgument::REQUIRED, 'Token to pass back to worker to verfiy')
            ))
        ));
        
        $this->setRequestTypes(array(
            'activateAction' => HTTP_METH_POST
        ));
    }
    
    
    public function activateAction()
    {        
        $arguments = $this->getArguments('activateAction');
        $this->getWorkerService()->verify($arguments->get('hostname'), $arguments->get('token'));
        return $this->sendResponse('ok');
    }
    
    
    /**
     *
     * @return WorkerService
     */
    private function getWorkerService() {
        return $this->container->get('simplytestable.services.workerservice');
    }
    
    


}
