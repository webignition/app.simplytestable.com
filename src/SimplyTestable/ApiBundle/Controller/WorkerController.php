<?php

namespace SimplyTestable\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use SimplyTestable\ApiBundle\Services\WorkerService;
use SimplyTestable\ApiBundle\Services\WorkerRequestActivationService;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputDefinition;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\WorkerActivationRequest;


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
        $worker = $this->getWorkerService()->get($this->getArguments('activateAction')->get('hostname'));
        $activationRequest = $this->getWorkerRequestActivationService()->get($worker);
        
        if ($activationRequest->getState()->equals($this->getWorkerRequestActivationService()->getStartingState())) {
            return $this->sendSuccessResponse();
        }
        
        $activationRequest->setState($this->getWorkerRequestActivationService()->getStartingState());
        $this->getWorkerRequestActivationService()->persistAndFlush($activationRequest);
        
        return $this->sendSuccessResponse();
    }
    
    
    /**
     *
     * @return WorkerService
     */
    private function getWorkerService() {
        return $this->container->get('simplytestable.services.workerservice');
    }
    
    
    /**
     *
     * @return SimplyTestable\ApiBundle\Services\WorkerActivationRequestService 
     */
    private function getWorkerRequestActivationService() {
        return $this->container->get('simplytestable.services.workeractivationrequestservice');        
    }
    
    


}
