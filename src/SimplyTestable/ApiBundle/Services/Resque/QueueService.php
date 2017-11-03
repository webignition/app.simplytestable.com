<?php
namespace SimplyTestable\ApiBundle\Services\Resque;

use BCC\ResqueBundle\Resque;
use Psr\Log\LoggerInterface;
use SimplyTestable\ApiBundle\Resque\Job\Job;
use BCC\ResqueBundle\Job as ResqueJob;

/**
 * Wrapper for \BCC\ResqueBundle\Resque that handles exceptions
 * when trying to interact with queues.
 *
 * Exceptions generally occur when trying to establish a socket connection to
 * a redis server that does not exist. This can happen as in some environments
 * where the integration with redis is optional.
 *
 */
class QueueService
{
    const QUEUE_KEY = 'queue';

    /**
     * @var Resque
     */
    private $resque;

    /**
     * @var string
     */
    private $environment = 'prod';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * @param Resque $resque
     * @param string $environment
     * @param LoggerInterface $logger
     * @param JobFactory $jobFactoryService
     */
    public function __construct(
        Resque $resque,
        $environment = 'prod',
        LoggerInterface $logger,
        JobFactory $jobFactoryService
    ) {
        $this->resque = $resque;
        $this->environment = $environment;
        $this->logger = $logger;
        $this->jobFactory = $jobFactoryService;
    }

    /**
     * @param string $queue
     * @param array $args
     *
     * @return boolean
     */
    public function contains($queue, $args = [])
    {
        try {
            return !is_null($this->findJobInQueue($queue, $args));
        } catch (\CredisException $credisException) {
            $this->logger->warning('ResqueQueueService::enqueue: Redis error ['.$credisException->getMessage().']');
        }

        return false;
    }


    /**
     * @param string $queue
     * @param array $args
     *
     * @return ResqueJob|null
     */
    private function findJobInQueue($queue, $args)
    {
        $jobs = $this->resque->getQueue($queue)->getJobs();

        foreach ($jobs as $job) {
            /* @var $job ResqueJob */

            if ($this->match($job, $args)) {
                return $job;
            }
        }

        return null;
    }

    /**
     * @param ResqueJob $job
     * @param array $args
     *
     * @return bool
     */
    private function match(ResqueJob $job, $args)
    {
        foreach ($args as $key => $value) {
            if (!isset($job->args[$key])) {
                return false;
            }

            if ($job->args[$key] != $value) {
                return false;
            }
        }

        return true;
    }


    /**
     * @param Job $job
     * @param bool $trackStatus
     * @return null|\Resque_Job_Status
     * @throws \CredisException
     * @throws \Exception
     */
    public function enqueue(Job $job, $trackStatus = false)
    {
        try {
            return $this->resque->enqueue($job, $trackStatus);
        } catch (\CredisException $credisException) {
            $this->logger->warning('ResqueQueueService::enqueue: Redis error ['.$credisException->getMessage().']');
        }

        return null;
    }

    /**
     * @param string $queue
     *
     * @return boolean
     */
    public function isEmpty($queue)
    {
        try {
            return $this->resque->getQueue($queue)->getSize() == 0;
        } catch (\Exception $exception) {
            $this->logger->warning('ResqueQueueService::isEmpty: Redis error ['.$exception->getMessage().']');
            return false;
        }
    }

    /**
     * @return Resque
     */
    public function getResque()
    {
        return $this->resque;
    }
}
