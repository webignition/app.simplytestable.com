<?php

namespace SimplyTestable\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\Task\Output;
use SimplyTestable\ApiBundle\Services\WorkerService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\JobService;

class TaskController extends ApiController
{
    private $workerHostname;
    
    public function __construct() {
        $this->setInputDefinitions(array(
            'completeAction' => new InputDefinition(array(
                new InputArgument('end_date_time', InputArgument::REQUIRED, 'Task end date and time'),
                new InputArgument('output', InputArgument::REQUIRED, 'Task output'),
                new InputArgument('contentType', InputArgument::REQUIRED, 'Task output content type')
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
        
        if ($this->getTaskService()->isAwaitingCancellation($task)) {
            $this->getTaskService()->cancel($task);
            return $this->sendSuccessResponse();
        }
        
        if (!$this->getTaskService()->isInProgress($task)) {
            return $this->sendSuccessResponse();
        }

        $endDateTime = new \DateTime($this->getArguments('startAction')->get('end_date_time'));
        $rawOutput = $this->getArguments('startAction')->get('output');
        
        $mediaTypeParser = new \webignition\InternetMediaType\Parser\Parser();
        $contentType = $mediaTypeParser->parse($this->getArguments('startAction')->get('contentType'));
        
        $output = new Output();
        $output->setOutput($rawOutput);
        $output->setContentType($contentType);

        $this->getTaskService()->complete($task, $endDateTime, $output);
        
        if (!$this->getJobService()->hasIncompleteTasks($task->getJob())) {
            $this->getJobService()->complete($task->getJob());
        }       
        
        return $this->sendSuccessResponse();
    }
    
    public function taskTypeCountAction($task_type, $state_name)
    {       
        $taskStatePrefix = 'task-';
        
        if (!$this->getTaskTypeService()->exists($task_type)) {
            return new Response('', 404);
        }
        
        if (!$this->getStateService()->has($taskStatePrefix . $state_name)) {
            return new Response('', 404);
        }
        
        $taskType = $this->getTaskTypeService()->getByName($task_type);       
        $state = $this->getStateService()->find($taskStatePrefix . $state_name);
        
        return new Response(json_encode($this->getTaskService()->getCountByTaskTypeAndState($taskType, $state)), 200);
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
    
    
    /**
     *
     * @return JobService
     */
    private function getJobService() {
        return $this->container->get('simplytestable.services.jobservice');
    }     
    
    
    /**
     *
     * @return TaskTypeService
     */
    private function getTaskTypeService() {
        return $this->container->get('simplytestable.services.tasktypeservice');
    }     
    
    /**
     *
     * @return StateService
     */
    private function getStateService() {
        return $this->container->get('simplytestable.services.stateservice');
    }      
}
