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
     * @var \Symfony\Component\HttpKernel\Log\LoggerInterface
     */
    private $logger;    
    
    
    /**
     *
     * @param EntityManager $entityManager
     * @param \SimplyTestable\ApiBundle\Services\JobService $jobService 
     * @param \SimplyTestable\ApiBundle\Services\TaskService $taskService
     */
    public function __construct(
            \SimplyTestable\ApiBundle\Services\JobService $jobService,
            \SimplyTestable\ApiBundle\Services\TaskService $taskService,
            \Symfony\Component\HttpKernel\Log\LoggerInterface $logger)
    {       
        $this->jobService = $jobService;
        $this->taskService = $taskService;
        $this->logger = $logger;
    }
    
    
    /**
     * Get a limited collection of queued tasks from each job that has queued tasks
     * 
     * @return array
     */
    public function selectTasks($limitPerJob = 1) {
        $this->logger->info('TaskAssignmentSelectionService:selectTasks:start');
        
        $jobs = $this->jobService->getJobsWithQueuedTasks();
        
        $taskInProgressState = $this->taskService->getInProgressState();
        $taskQueuedForAssignmentState = $this->taskService->getQueuedForAssignmentState();
        
        $tasks = array();
        foreach ($jobs as $jobIndex => $job) {
            /* @var $job Job */
            $this->logger->info('TaskAssignmentSelectionService:selectTasks [job'.$jobIndex.'] ['.$job->getId().'] ['.$job->getWebsite().']');

            $inProgressTaskCount = $this->taskService->getCountByJobAndState($job, $taskInProgressState);
            $queuedForAssignmentTaskCount = $this->taskService->getCountByJobAndState($job, $taskQueuedForAssignmentState);
            
            $limitForThisJob = $limitPerJob - $inProgressTaskCount - $queuedForAssignmentTaskCount;                   
            
            $this->logger->info('TaskAssignmentSelectionService:selectTasks:inProgressTaskCount: [job'.$jobIndex.'] ['.$inProgressTaskCount.']');
            $this->logger->info('TaskAssignmentSelectionService:selectTasks:queuedForAssignmentTaskCount: [job'.$jobIndex.'] ['.$queuedForAssignmentTaskCount.']');
            $this->logger->info('TaskAssignmentSelectionService:selectTasks:limitForThisJob: [job'.$jobIndex.'] ['.$limitForThisJob.']');
            
            if ($limitForThisJob > 0) {
                $tasks = array_merge($tasks, $this->jobService->getQueuedTasks($job, $limitForThisJob));
            }         
        }
        
        return $tasks;
    }   
    
}
