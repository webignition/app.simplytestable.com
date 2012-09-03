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
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\ResqueJobFactoryService 
     */
    private $resqueJobFactoryService;
    
    
    /**
     *
     * @var \Resque\Redis
     */
    private $resqueRedis;
    
    
    /**
     *
     * @var string
     */
    private $resquePrefix;
    
    
    public function __construct(
            \Glit\ResqueBundle\Resque\Queue $queueManager,
            \Symfony\Component\HttpKernel\Log\LoggerInterface $logger,
            \SimplyTestable\ApiBundle\Services\ResqueJobFactoryService $resqueJobFactoryService,
            $resquePrefix
            
    ) {
        $this->queueManager = $queueManager;
        $this->logger = $logger;
        $this->resqueJobFactoryService = $resqueJobFactoryService;
        $this->resquePrefix = $resquePrefix;
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
     * @param string $queue_name
     * @param array $args
     * @return boolean 
     */
    public function remove($queue_name, $args = null) {        
        try {
            return \Resque\Resque::redis()->lrem('queue:' . $queue_name, 1, $this->findRedisValue($queue_name, $args)) == 1;           
        } catch (\Exception $exception) {
            $this->logger->warn('ResqueQueueService::add: Redis error ['.$exception->getMessage().']');
            return false;
        }
    }
    
    
    
    /**
     *
     * @param string $queue_name
     * @param array $args
     * @return string
     */
    private function findRedisValue($queue_name, $args) {                        
        $queueLength = $this->getQueueLength($queue_name);
        
        for ($queueIndex = 0; $queueIndex < $queueLength; $queueIndex++) {            
            $jobDetails = json_decode(\Resque\Resque::redis()->lindex('queue:' . $queue_name, $queueIndex));
            
            if ($this->match($jobDetails, $queue_name, $args)) {
                return json_encode($jobDetails);
            }
        }
        
        return null;
    }
    
    
    /**
     *
     * @param string $jobDetails
     * @param string $queue_name
     * @param array $args
     * @return boolean 
     */
    private function match($jobDetails, $queue_name, $args) {        
        $job_name = $this->resqueJobFactoryService->getJobName($queue_name);
        
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
        return $this->getResqueRedis()->llen('queue:' . $queue_name);
    }
    
    
    
    /**
     *
     * @return \Resque\Redis
     */
    private function getResqueRedis() {
        var_dump($this->resquePrefix, json_encode($this->resquePrefix));
        
        if (is_null($this->resqueRedis)) {
            $this->resqueRedis = \Resque\Resque::redis();
            $this->resqueRedis->prefix($this->resquePrefix . ':resque:');
        }
        
        return $this->resqueRedis;
    }    
    
    
}