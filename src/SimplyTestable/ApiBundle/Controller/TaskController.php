<?php

namespace SimplyTestable\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\Task\Output;
use SimplyTestable\ApiBundle\Services\WorkerService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;

class TaskController extends ApiController {
    
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
            'completeAction' => \Guzzle\Http\Message\Request::POST,
            'completeByUrlAndTaskTypeAction' => \Guzzle\Http\Message\Request::POST
        ));       
    }  
    
    
    public function completeAction($canonical_url, $task_type, $parameter_hash) {
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        $task_type = urldecode($task_type);
        if (!$this->getTaskTypeService()->exists($task_type)) {
            return $this->sendFailureResponse();
        }        
        
        $taskType = $this->getTaskTypeService()->getByName($task_type);        
        
        $tasks = $this->getTaskService()->getEquivalentTasks($canonical_url, $taskType, $parameter_hash, $this->getTaskService()->getIncompleteStates());

        if (count($tasks) === 0) {
            return $this->sendGoneResponse();
        }
        
        $endDateTime = new \DateTime($this->getArguments('completeByUrlAndTaskTypeAction')->get('end_date_time'));
        $rawOutput = $this->getArguments('completeByUrlAndTaskTypeAction')->get('output');
        
        $mediaTypeParser = new \webignition\InternetMediaType\Parser\Parser();
        $contentType = $mediaTypeParser->parse($this->getArguments('completeByUrlAndTaskTypeAction')->get('contentType'));
        
        $output = new Output();
        $output->setOutput($rawOutput);
        $output->setContentType($contentType);
        $output->setErrorCount($this->getArguments('completeByUrlAndTaskTypeAction')->get('errorCount'));
        $output->setWarningCount($this->getArguments('completeByUrlAndTaskTypeAction')->get('warningCount'));
                
        $state = $this->getTaskEndState($this->getArguments('completeByUrlAndTaskTypeAction')->get('state'));

        $urlDiscoveryTaskType = $this->getTaskTypeService()->getByName('URL discovery');

        foreach ($tasks as $task) {
            /* @var $task Task */

            if ($task->hasOutput() && $this->getTaskOutputJoinerFactoryService()->hasTaskOutputJoiner($task)) {
                $output = $this->getTaskOutputJoinerFactoryService()->getTaskOutputJoiner($task)->join(array(
                    $task->getOutput(),
                    $output
                ));
            }

            $this->getTaskService()->complete($task, $endDateTime, $output, $state, false);

            if ($task->getType()->equals($urlDiscoveryTaskType)) {
                $this->getCrawlJobContainerService()->processTaskResults($task);
            }

            if (!$this->getJobService()->hasIncompleteTasks($task->getJob())) {
                $this->getJobService()->complete($task->getJob());
            }

            if ($task->getType()->equals($this->getTaskTypeService()->getByName('URL discovery'))) {
                if ($this->getJobService()->isCompleted($task->getJob())) {
                    $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($task->getJob());

                    foreach ($crawlJobContainer->getParentJob()->getRequestedTaskTypes() as $taskType) {
                        /* @var $taskType TaskType */
                        $taskTypeParameterDomainsToIgnoreKey = strtolower(str_replace(' ', '-', $taskType->getName())) . '-domains-to-ignore';

                        if ($this->container->hasParameter($taskTypeParameterDomainsToIgnoreKey)) {
                            $this->getJobPreparationService()->setPredefinedDomainsToIgnore($taskType, $this->container->getParameter($taskTypeParameterDomainsToIgnoreKey));
                        }
                    }

                    $this->getJobPreparationService()->prepareFromCrawl($crawlJobContainer);

                    $limit = $this->container->getParameter('tasks_per_job_per_worker_count') * count($this->getWorkerService()->getActiveCollection());

                    $this->getTaskQueueService()->setLimit($limit);
                    $this->getTaskQueueService()->setJob($crawlJobContainer->getParentJob());

                    $this->getResqueQueueService()->enqueue(
                        $this->getResqueJobFactoryService()->create(
                            'task-assign-collection',
                            ['ids' => implode(',', $this->getTaskQueueService()->getNext())]
                        )
                    );
                }
            }
        }

        return $this->sendSuccessResponse();
    }

    
    /**
     *
     * @param string $stateFromRequest
     * @return \SimplyTestable\ApiBundle\Entity\State
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
     * @return WorkerService
     */
    private function getWorkerService() {
        return $this->container->get('simplytestable.services.workerservice');
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
     * @return \SimplyTestable\ApiBundle\Services\JobPreparationService
     */
    private function getJobPreparationService() {
        return $this->container->get('simplytestable.services.jobpreparationservice');
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
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\CrawlJobContainerService
     */
    private function getCrawlJobContainerService() {
        return $this->container->get('simplytestable.services.crawljobcontainerservice');
    }

    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\Resque\QueueService
     */
    private function getResqueQueueService() {
        return $this->get('simplytestable.services.resque.queueService');
    }


    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\Resque\JobFactoryService
     */
    private function getResqueJobFactoryService() {
        return $this->get('simplytestable.services.resque.jobFactoryService');
    }
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\TaskOutputJoiner\FactoryService
     */    
    private function getTaskOutputJoinerFactoryService() {
        return $this->container->get('simplytestable.services.TaskOutputJoinerServiceFactory');
    }


    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\Task\QueueService
     */
    private function getTaskQueueService() {
        return $this->container->get('simplytestable.services.task.queueservice');
    }
}

