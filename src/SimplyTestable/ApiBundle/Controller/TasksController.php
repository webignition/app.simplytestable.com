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

class TasksController extends ApiController {
    private $workerHostname;
    
    public function __construct() {
        $this->setInputDefinitions([
            'requestAction' => new InputDefinition([
                new InputArgument('worker_hostname', InputArgument::REQUIRED, 'Hostname of worker making request'),
                new InputArgument('worker_token', InputArgument::REQUIRED, 'Requesting worker\'s auth token'),
                new InputArgument('limit', InputArgument::OPTIONAL, 'Number of tasks to request')
            ])
        ]);

        $this->setRequestTypes(array(
            'requestAction' => \Guzzle\Http\Message\Request::GET
        ));
    }  
    
    
    public function requestAction() {
//        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
//            return $this->sendServiceUnavailableResponse();
//        }
//
//        $task_type = urldecode($task_type);
//        if (!$this->getTaskTypeService()->exists($task_type)) {
//            return $this->sendFailureResponse();
//        }
//
//        $taskType = $this->getTaskTypeService()->getByName($task_type);
//
//        $tasks = $this->getTaskService()->getEquivalentTasks($canonical_url, $taskType, $parameter_hash, $this->getTaskService()->getIncompleteStates());
//
//        if (count($tasks) === 0) {
//            return $this->sendGoneResponse();
//        }
//
//        $endDateTime = new \DateTime($this->getArguments('completeByUrlAndTaskTypeAction')->get('end_date_time'));
//        $rawOutput = $this->getArguments('completeByUrlAndTaskTypeAction')->get('output');
//
//        $mediaTypeParser = new \webignition\InternetMediaType\Parser\Parser();
//        $contentType = $mediaTypeParser->parse($this->getArguments('completeByUrlAndTaskTypeAction')->get('contentType'));
//
//        $output = new Output();
//        $output->setOutput($rawOutput);
//        $output->setContentType($contentType);
//        $output->setErrorCount($this->getArguments('completeByUrlAndTaskTypeAction')->get('errorCount'));
//        $output->setWarningCount($this->getArguments('completeByUrlAndTaskTypeAction')->get('warningCount'));
//
//        $state = $this->getTaskEndState($this->getArguments('completeByUrlAndTaskTypeAction')->get('state'));
//
//        $urlDiscoveryTaskType = $this->getTaskTypeService()->getByName('URL discovery');
//
//        $processedTaskCount = 0;
//
//        foreach ($tasks as $task) {
//            $processedTaskCount++;
//
//            /* @var $task Task */
//
//            if ($task->hasOutput() && $this->getTaskOutputJoinerFactoryService()->hasTaskOutputJoiner($task)) {
//                $output = $this->getTaskOutputJoinerFactoryService()->getTaskOutputJoiner($task)->join(array(
//                    $task->getOutput(),
//                    $output
//                ));
//            }
//
//            $this->getTaskService()->complete($task, $endDateTime, $output, $state, false);
//
//            if ($task->getType()->equals($urlDiscoveryTaskType)) {
//                $this->getCrawlJobContainerService()->processTaskResults($task);
//            }
//
//            if (!$this->getJobService()->hasIncompleteTasks($task->getJob())) {
//                $this->getJobService()->complete($task->getJob());
//            }
//
//            if ($task->getType()->equals($this->getTaskTypeService()->getByName('URL discovery')) && $this->getJobService()->isCompleted($task->getJob())) {
//                $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($task->getJob());
//
//                foreach ($crawlJobContainer->getParentJob()->getRequestedTaskTypes() as $taskType) {
//                    /* @var $taskType TaskType */
//                    $taskTypeParameterDomainsToIgnoreKey = strtolower(str_replace(' ', '-', $taskType->getName())) . '-domains-to-ignore';
//
//                    if ($this->container->hasParameter($taskTypeParameterDomainsToIgnoreKey)) {
//                        $this->getJobPreparationService()->setPredefinedDomainsToIgnore($taskType, $this->container->getParameter($taskTypeParameterDomainsToIgnoreKey));
//                    }
//                }
//
//                $this->getJobPreparationService()->prepareFromCrawl($crawlJobContainer);
//            }
//        }
//
//        if ($this->getResqueQueueService()->isEmpty('task-assignment-selection')) {
//            $this->getResqueQueueService()->enqueue(
//                $this->getResqueJobFactoryService()->create(
//                    'task-assignment-selection'
//                )
//            );
//        }
//
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
}

