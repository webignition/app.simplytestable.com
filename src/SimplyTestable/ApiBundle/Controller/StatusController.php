<?php

namespace SimplyTestable\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use SimplyTestable\ApiBundle\Services\WorkerService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\WorkerRequestActivationService;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputDefinition;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\WorkerActivationRequest;


class StatusController extends ApiController
{  
    
    
    public function indexAction()
    {        
        $workers = $this->getWorkerService()->getEntityRepository()->findAll();
        
        $workerSummary = array();
        foreach ($workers as $worker) {
            $workerSummary[] = array(
                'hostname' => $worker->getHostname(),
                'state' => $worker->getPublicSerializedState()
            );
        }                
        
        $responseData = array(
            'state' => $this->getApplicationStateService()->getState(),
            'workers' => $workerSummary,
            'version' => $this->getLatestGitHash(),
            'task_throughput_per_minute' => $this->getTaskService()->getEntityRepository()->getThroughputSince(new \DateTime('-1 minute')),
            'in_progress_job_count' => $this->getJobService()->getEntityRepository()->getCountByState($this->getJobService()->getInProgressState())
        );
        
        return $this->sendResponse($responseData);        
    }
    
    private function getLatestGitHash() {
        return trim(shell_exec("git log | head -1 | awk {'print $2;'}"));
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
        return $this->container->get('simplytestable.services.taskService');
    }    
    
    /**
     *
     * @return JobService
     */
    private function getJobService() {
        return $this->container->get('simplytestable.services.jobService');
    }        


}
