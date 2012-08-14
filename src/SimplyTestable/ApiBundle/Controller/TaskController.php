<?php

namespace SimplyTestable\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Services\WorkerService;
use SimplyTestable\ApiBundle\Services\TaskService;

class TaskController extends ApiController
{
    private $workerHostname;
    
    public function __construct() {
        $this->setInputDefinitions(array(
            'completeAction' => new InputDefinition(array(
                new InputArgument('end_date_time', InputArgument::REQUIRED, 'Task end date and time'),
                new InputArgument('output', InputArgument::REQUIRED, 'Task output')
            ))
        ));
        
        $this->setRequestTypes(array(
            'completeAction' => HTTP_METH_POST
        ));
    }    
    
    public function completeAction($worker_hostname, $remote_task_id)
    {      
        $this->workerHostname = $worker_hostname;        
        
        $task = $this->getTaskService()->getByWorkerAndRemoteId($this->getWorker(), $remote_task_id);
        
        if (is_null($task)) {
            return $this->sendFailureResponse();
        }

        $endDateTime = new \DateTime($this->getArguments('startAction')->get('end_date_time'));
        $output = $this->getArguments('startAction')->get('output');
        
        if (!$task->getState()->equals($this->getTaskService()->getInProgressState())) {
            return $this->sendFailureResponse();
        }       

        $this->getTaskService()->complete($task, $endDateTime, $output);
        
        return $this->sendSuccessResponse();
    }
    
    /**
     *
     * @return Worker
     */
    private function getWorker() {
        return $this->get('simplytestable.services.workerservice')->fetch($this->workerHostname);
    }
    
    
    /**
     *
     * @return TaskService
     */
    private function getTaskService() {
        return $this->container->get('simplytestable.services.taskservice');
    }   
}
