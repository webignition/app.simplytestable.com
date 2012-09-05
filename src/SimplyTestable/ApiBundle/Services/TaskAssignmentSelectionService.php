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
     * Get the oldest queued tasks from each job that has queued tasks
     * 
     * @return array
     */
    public function selectTasks($limitPerJob = 1) {
        $jobs = $this->jobService->getJobsWithQueuedTasks();
        
        $tasks = array();
        foreach ($jobs as $job) {            
            $tasksForJob = $this->jobService->getOldestQueuedTasks($job, $limitPerJob);
            foreach ($tasksForJob as $task) {
                $tasks[] = $task;
            }            
        }
        
        return $tasks;
    }   
    
}
