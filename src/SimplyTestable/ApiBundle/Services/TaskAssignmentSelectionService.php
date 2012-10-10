<?php
namespace SimplyTestable\ApiBundle\Services;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;

class TaskAssignmentSelectionService {
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\JobService
     */
    private $jobService;
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\TaskService
     */
    private $taskService;      
    
    
    /**
     *
     * @param EntityManager $entityManager
     * @param \SimplyTestable\ApiBundle\Services\JobService $jobService 
     * @param \SimplyTestable\ApiBundle\Services\TaskService $taskService
     */
    public function __construct(
            \SimplyTestable\ApiBundle\Services\JobService $jobService,
            \SimplyTestable\ApiBundle\Services\TaskService $taskService)
    {       
        $this->jobService = $jobService;
        $this->taskService = $taskService;
    }
    
    
    /**
     * Get a limited collection of queued tasks from each job that has queued tasks
     * 
     * @return array
     */
    public function selectTasks($limitPerJob = 1) {
        $jobs = $this->jobService->getJobsWithQueuedTasks();
        
        $taskInProgressState = $this->taskService->getInProgressState();
        
        $tasks = array();
        foreach ($jobs as $job) {
            $inProgressTaskCount = $this->taskService->getCountByJobAndState($job, $taskInProgressState);
            $limitForThisJob = $limitPerJob - $inProgressTaskCount;
            
            if ($limitForThisJob > 0) {
                $tasks = array_merge($tasks, $this->jobService->getQueuedTasks($job, $limitForThisJob));
            }         
        }
        
        return $tasks;
    }   
    
}
