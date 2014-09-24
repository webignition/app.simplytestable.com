<?php
namespace SimplyTestable\ApiBundle\Services\Resque;

use BCC\ResqueBundle\Resque;
use SimplyTestable\ApiBundle\Resque\Job\Job;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use SimplyTestable\ApiBundle\Services\Resque\JobFactoryService;


/**
 * Wrapper for \BCC\ResqueBundle\Resque that handles exceptions
 * when trying to interact with queues.
 * 
 * Exceptions generally occur when trying to establish a socket connection to
 * a redis server that does not exist. This can happen as in some environments
 * where the integration with redis is optional.
 * 
 */
class QueueService {

    const QUEUE_KEY = 'queue';

    /**
     *
     * @var Resque
     */
    private $resque;


    /**
     * @var string
     */
    private $environment = 'prod';

    /**
     *
     * @var LoggerInterface
     */
    private $logger;


    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\Resque\JobFactoryService
     */
    private $jobFactoryService;


    public function __construct(Resque $resque, $environment = 'prod', LoggerInterface $logger, JobFactoryService $jobFactoryService) {
        $this->resque = $resque;
        $this->environment = $environment;
        $this->logger = $logger;
        $this->jobFactoryService = $jobFactoryService;
    }


    /**
     *
     * @param string $queue_name
     * @param array $args
     * @return boolean
     */
    public function contains($queue_name, $args = []) {
        try {
            return !is_null($this->findRedisValue($queue_name, $args));
        } catch (\CredisException $credisException) {
            $this->logger->warning('ResqueQueueService::enqueue: Redis error ['.$credisException->getMessage().']');
        }

        return false;
    }



    /**
     *
     * @param string $queue
     * @param array $args
     * @return string
     */
    private function findRedisValue($queue, $args) {
        $queueLength = $this->getQueueLength($queue);

        for ($queueIndex = 0; $queueIndex < $queueLength; $queueIndex++) {
            $jobDetails = json_decode(\Resque::redis()->lindex(self::QUEUE_KEY . ':' . $queue, $queueIndex));

            if ($this->match($jobDetails, $queue, $args)) {
                return json_encode($jobDetails);
            }
        }

        return null;
    }


    /**
     *
     * @param string $jobDetails
     * @param string $queue
     * @param array $args
     * @return boolean
     */
    private function match($jobDetails, $queue, $args) {
        if (!isset($jobDetails->class)) {
            return false;
        }

        if ($jobDetails->class != $this->jobFactoryService->getJobClassName($queue)) {
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
     * @param string $queue
     * @return int
     */
    private function getQueueLength($queue) {
        return \Resque::redis()->llen(self::QUEUE_KEY . ':' . $queue);
    }


    /**
     * @param Job $job
     * @param bool $trackStatus
     * @return null|\Resque_Job_Status
     * @throws \CredisException
     * @throws \Exception
     */
    public function enqueue(Job $job, $trackStatus = false) {
        try {
            return $this->resque->enqueue($job, $trackStatus);
        } catch (\CredisException $credisException) {
            $this->logger->warning('ResqueQueueService::enqueue: Redis error ['.$credisException->getMessage().']');
        }
    }


    /**
     *
     * @param string $queue
     * @return boolean
     */
    public function isEmpty($queue) {
        return $this->getQueueLength($queue) == 0;
    }


    /**
     *
     * @param string $queue
     * @param array $argCollection
     */
    public function removeCollection($queue, $argCollection) {
        $values = $this->findRedisValues($queue, $argCollection);

        foreach ($values as $redisValue) {
            \Resque::redis()->lrem(self::QUEUE_KEY . ':' . $queue, 1, $redisValue);
        }
    }


    /**
     * @param $queue
     * @param $argCollection
     * @return array
     */
    private function findRedisValues($queue, $argCollection) {
        $queueLength = $this->getQueueLength($queue);
        $values = array();

        for ($queueIndex = 0; $queueIndex < $queueLength; $queueIndex++) {
            $job_details = json_decode(@\Resque::redis()->lindex(self::QUEUE_KEY . ':' . $queue, $queueIndex));

            foreach ($argCollection as $args) {
                if ($this->match($job_details, $queue, $args)) {
                    $values[] = json_encode($job_details);
                }
            }
        }

        return $values;
    }


    /**
     *
     * @param string $queue
     * @param array $args
     * @return boolean
     */
    public function dequeue($queue, $args = null) {
        try {
            return \Resque::redis()->lrem(self::QUEUE_KEY . ':' . $queue, 1, $this->findRedisValue($queue, $args)) == 1;
        } catch (\Exception $exception) {
            $this->logger->warning('ResqueQueueService::dequeue: Redis error ['.$exception->getMessage().']');
            return false;
        }
    }
    
}