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
    
    
    /**
     *
     * @var \Glit\ResqueBundle\Resque\Queue 
     */
    private $queueManager;
    
    /**
     *
     * @var \Glit\ResqueBundle\Resque\Queue
     */
    private $logger;
    
    
    public function __construct(
            \Glit\ResqueBundle\Resque\Queue $queueManager,
            \Symfony\Component\HttpKernel\Log\LoggerInterface $logger
    ) {
        $this->queueManager = $queueManager;
        $this->logger = $logger;
        
    }
    
    
    /**
     *
     * @param string $job_name Fully qualified job class name
     * @param string $queue_name Name of the queue to which to add the job
     * @param array $args
     * @return int 
     */
    public function add($job_name, $queue_name, $args = null) {
        try {
            return @$this->queueManager->add($job_name, $queue_name, $args);            
        } catch (\Exception $exception) {
            $this->logger->warn('ResqueQueueService::add: Redis error ['.$exception->getMessage().']');
            return 0;
        }      
    }
    
    
}