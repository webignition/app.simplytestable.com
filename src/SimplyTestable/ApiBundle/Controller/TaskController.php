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
                new InputArgument('contentType', InputArgument::REQUIRED, 'Task output content type'),
                new InputArgument('state', InputArgument::REQUIRED, 'Task ending state'),
                new InputArgument('errorCount', InputArgument::REQUIRED, 'Task error count'),
                new InputArgument('warningCount', InputArgument::REQUIRED, 'Task warning count')
            ))
        ));
        
        $this->setRequestTypes(array(
            'completeAction' => HTTP_METH_POST
        ));
    }    
    
    public function completeAction($worker_hostname, $remote_task_id)
    {   
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }          
        
        $this->workerHostname = $worker_hostname;        
        
        $task = $this->getTaskService()->getByWorkerAndRemoteId($this->getWorker(), $remote_task_id);
        
        if (is_null($task)) {
            return $this->sendNotFoundResponse();
        }
        
        if ($this->getTaskService()->isAwaitingCancellation($task)) {
            $this->getTaskService()->cancel($task);
            return $this->sendSuccessResponse();
        }
        
        if (!$this->getTaskService()->isInProgress($task)) {
            return $this->sendSuccessResponse();
        }

        $endDateTime = new \DateTime($this->getArguments('startAction')->get('end_date_time'));
        $rawOutput = $this->getArguments('completeAction')->get('output');
        
        $mediaTypeParser = new \webignition\InternetMediaType\Parser\Parser();
        $contentType = $mediaTypeParser->parse($this->getArguments('completeAction')->get('contentType'));
        
        $output = new Output();
        $output->setOutput($rawOutput);
        $output->setContentType($contentType);
        $output->setErrorCount($this->getArguments('completeAction')->get('errorCount'));
        $output->setWarningCount($this->getArguments('completeAction')->get('warningCount'));
                
        $state = $this->getTaskEndState($this->getArguments('completeAction')->get('state'));

        $this->getTaskService()->complete($task, $endDateTime, $output, $state);
        
        if (!$this->getJobService()->hasIncompleteTasks($task->getJob())) {
            $this->getJobService()->complete($task->getJob());
        }       
        
        return $this->sendSuccessResponse();
    }
    
    
    /**
     *
     * @param string $stateFromRequest
     * @return \SimlpyTestable\ApiBundle\Entity\State 
     */
    private function getTaskEndState($stateFromRequest) {        
        if ($stateFromRequest == $this->getTaskService()->getFailedNoRetryAvailableState()->getName()) {
            return $this->getTaskService()->getFailedNoRetryAvailableState();
        }
        
        if ($stateFromRequest == $this->getTaskService()->getFailedRetryAvailableState()->getName()) {
            return $this->getTaskService()->getFailedRetryAvailableState();
        }
        
        if ($stateFromRequest == $this->getTaskService()->getFailedRetryLimitReachedState()->getName()) {
            return $this->getTaskService()->getFailedRetryLimitReachedState();
        }
        
        if ($stateFromRequest == $this->getTaskService()->getSkippedState()->getName()) {
            return $this->getTaskService()->getSkippedState();
        }        
        
        return $this->getTaskService()->getCompletedState();
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
