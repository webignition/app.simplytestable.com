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
            $resquePrefix
            
    ) {
        $this->queueManager = $queueManager;
        $this->logger = $logger;
        $this->resquePrefix = $resquePrefix;
    }
    
    
    /**
     *
     * @param string $job_name Name of the job class
     * @param string $queue_name Name of the queue to which to add the job
     * @param array $args
     * @return string 
     */
    public function add($job_name, $queue_name, $args = null) {                
        try {
            return @$this->queueManager->add($job_name, $queue_name, $args);            
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
            return \Resque\Resque::redis()->lrem(self::QUEUE_KEY . ':' . $queue_name, 1, $this->findRedisValue($job_name, $queue_name, $args)) == 1;           
        } catch (\Exception $exception) {
            $this->logger->warn('ResqueQueueService::add: Redis error ['.$exception->getMessage().']');
            return false;
        }
    }
    
    
    
    /**
     *
     * @param string $job_name
     * @param string $queue_name
     * @param array $argCollection 
     */
    public function removeCollection($job_name, $queue_name, $argCollection) {        
        $values = $this->findRedisValues($job_name, $queue_name, $argCollection);      
        
        foreach ($values as $redisValue) {
            \Resque\Resque::redis()->lrem(self::QUEUE_KEY . ':' . $queue_name, 1, $redisValue);
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
            $jobDetails = json_decode(@\Resque\Resque::redis()->lindex(self::QUEUE_KEY . ':' . $queue_name, $queueIndex));
            
            if ($this->match($jobDetails, $job_name, $args)) {
                return json_encode($jobDetails);
            }
        }
        
        return null;
    }
    
    
    private function findRedisValues($job_name, $queue_name, $argCollection) {        
        $queueLength = $this->getQueueLength($queue_name);
        $values = array();
        
        for ($queueIndex = 0; $queueIndex < $queueLength; $queueIndex++) {            
            $job_details = json_decode(@\Resque\Resque::redis()->lindex(self::QUEUE_KEY . ':' . $queue_name, $queueIndex));
            
            foreach ($argCollection as $args) {                
                if ($this->match($job_details, $job_name, $args)) {
                    $values[] = json_encode($job_details);
                }                
            }
        }
        
        return $values;        
    }
    
    
    /**
     *
     * @param \stdClass $job_details
     * @param string $job_name
     * @param array $args
     * @return boolean 
     */
    private function match($job_details, $job_name, $args) {        
        if (!isset($job_details->class)) {
            return false;
        }
        
        if ($job_details->class != $job_name) {
            return false;
        }
        
        if (!isset($job_details->args)) {
            return false;
        }
        
        if (!isset($job_details->args[0])) {
            return false;
        }        

        foreach ($args as $key => $value) {            
            if (!isset($job_details->args[0]->$key)) {
                return false;
            }
            
            if ($job_details->args[0]->$key != $value) {
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
    public function getQueueLength($queue_name) {
        try {
            return @$this->getResqueRedis()->llen(self::QUEUE_KEY . ':' . $queue_name);          
        } catch (\Exception $exception) {
            $this->logger->warn('ResqueQueueService::add: Redis error ['.$exception->getMessage().']');
            return 0;
        }
    }
    
    
    /**
     *
     * @param string $queue_name
     * @return boolean
     */
    public function isEmpty($queue_name) {
        return $this->getQueueLength($queue_name) == 0;        
    }
    
    
    
    /**
     *
     * @return \Resque\Redis
     */
    private function getResqueRedis() {        
        if (is_null($this->resqueRedis)) {
            $this->resqueRedis = \Resque\Resque::redis();
            $this->resqueRedis->prefix($this->resquePrefix . ':'.self::RESQUE_KEY.':');
        }
        
        return $this->resqueRedis;
    }    
    
    
}