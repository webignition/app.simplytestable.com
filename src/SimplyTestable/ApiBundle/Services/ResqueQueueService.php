<?php
namespace SimplyTestable\ApiBundle\Services;


/**
 * Wrapper for \Glit\ResqueBundle\Resque\Queue that handles exceptions
 * when trying to interact with queues.
 * 
 * Exceptions generally occur when trying to establish a socket connection to
 * a redis server that does not exist. This can happen as in some environments
 * the integration with redis is optional.
 * 
 */
class ResqueQueueService { 
    
    const RESQUE_KEY = 'resque';
    const QUEUE_KEY = 'queue';
    
    /**
     *
     * @var \Glit\ResqueBundle\Resque\Queue 
     */
    private $queueManager;
    
    /**
     *
     * @var \Symfony\Component\HttpKernel\Log\LoggerInterface
     */
    private $logger;
    
    
    private $resqueJobFactoryService;
    
    
    public function __construct(
            \Glit\ResqueBundle\Resque\Queue $queueManager,
            \Symfony\Component\HttpKernel\Log\LoggerInterface $logger,
            \SimplyTestable\ApiBundle\Services\ResqueJobFactoryService $resqueJobFactoryService
    ) {
        $this->queueManager = $queueManager;
        $this->logger = $logger;
        $this->resqueJobFactoryService = $resqueJobFactoryService;
    }
    
    
    /**
     *
     * @param string $queue_name Name of the queue to which to add the job
     * @param array $args
     * @return string 
     */
    public function add($queue_name, $args = null) {                
        try {
            return @$this->queueManager->add($this->resqueJobFactoryService->getJobName($queue_name), $queue_name, $args);            
        } catch (\Exception $exception) {
            $this->logger->warn('ResqueQueueService::add: Redis error ['.$exception->getMessage().']');
            return 0;
        }      
    }
    
    
    /**
     *
     * @param string $job_name
     * @param string $queue_name
     * @param array $args
     * @return boolean 
     */
    public function remove($job_name, $queue_name, $args = null) {
        try {
            return \Resque\Resque::redis()->lrem('queue:task-assign', 1, $this->findRedisValue($job_name, $queue_name, $args)) == 1;           
        } catch (\Exception $exception) {
            $this->logger->warn('ResqueQueueService::add: Redis error ['.$exception->getMessage().']');
            return false;
        }
    }
    
    
    
    /**
     *
     * @param string $job_name
     * @param string $queue_name
     * @param array $args
     * @return string
     */
    private function findRedisValue($job_name, $queue_name, $args) {                
        $queueLength = $this->getQueueLength($queue_name);        
        
        for ($queueIndex = 0; $queueIndex < $queueLength; $queueIndex++) {            
            $jobDetails = json_decode(\Resque\Resque::redis()->lindex('queue:task-assign', $queueIndex));
            
            if ($this->match($jobDetails, $job_name, $args)) {
                return json_encode($jobDetails);
            }
        }
        
        return null;
    }
    
    
    /**
     *
     * @param string $jobDetails
     * @param string $job_name
     * @param array $args
     * @return boolean 
     */
    private function match($jobDetails, $job_name, $args) {        
        if (!isset($jobDetails->class)) {
            return false;
        }
        
        if ($jobDetails->class != $job_name) {
            return false;
        }
        
        if (!isset($jobDetails->args)) {
            return false;
        }
        
        if (!isset($jobDetails->args[0])) {
            return false;
        }        

        foreach ($args as $key => $value) {            
            if (!isset($jobDetails->args[0]->$key)) {
                return false;
            }
            
            if ($jobDetails->args[0]->$key != $value) {
                return false;
            }
        }
        
        return true;
    }
    
    
    /**
     *
     * @param string $queue_name
     * @return int
     */
    private function getQueueLength($queue_name) {
        return \Resque\Resque::redis()->llen('queue:' . $queue_name);
    }    
}