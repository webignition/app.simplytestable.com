<?php
namespace SimplyTestable\ApiBundle\Services\Resque;

use SimplyTestable\ApiBundle\Resque\Job\Job;

class JobFactory
{
    const EXCEPTION_MESSAGE_INVALID_QUEUE = 'Queue "%s" is not valid';
    const EXCEPTION_CODE_INVALID_QUEUE = 1;
    const EXCEPTION_MESSAGE_MISSING_REQUIRED_ARG = 'Required argument "%s" is missing';
    const EXCEPTION_CODE_MISSING_REQUIRED_ARG = 2;
    const KEY_SERVICE_IDS = 'serviceIds';
    const KEY_JOB_CLASS_NAME = 'jobClassName';
    const KEY_REQUIRED_ARGS = 'requiredArgs';
    const KEY_PARAMETERS = 'parameters';

    /**
     * @var array
     */
    private $queues;

    /**
     * @param array $queues
     */
    public function __construct($queues)
    {
        $this->queues = $queues;
    }

    /**
     * @param string $queue
     * @param array $args
     *
     * @return Job
     */
    public function create($queue, $args = [])
    {
        if (!isset($this->queues[$queue])) {
            throw new \InvalidArgumentException(
                sprintf(
                    self::EXCEPTION_MESSAGE_INVALID_QUEUE,
                    $queue
                ),
                self::EXCEPTION_CODE_INVALID_QUEUE
            );
        }

        $queueProperties = $this->queues[$queue];

        if (isset($queueProperties[self::KEY_REQUIRED_ARGS])) {
            $requiredArgs = $queueProperties[self::KEY_REQUIRED_ARGS];

            foreach ($requiredArgs as $requiredArg) {
                if (!isset($args[$requiredArg])) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            self::EXCEPTION_MESSAGE_MISSING_REQUIRED_ARG,
                            $requiredArg
                        ),
                        self::EXCEPTION_CODE_MISSING_REQUIRED_ARG
                    );
                }
            }
        }

        if (isset($queueProperties[self::KEY_SERVICE_IDS])) {
            $args[self::KEY_SERVICE_IDS] = $queueProperties[self::KEY_SERVICE_IDS];
        }


        if (isset($queueProperties[self::KEY_PARAMETERS])) {
            $args[self::KEY_PARAMETERS] = $queueProperties[self::KEY_PARAMETERS];
        }

        $jobClassName = $queueProperties[self::KEY_JOB_CLASS_NAME];

        /* @var $job Job */
        $job = new $jobClassName($args);
        $job->setQueue($queue);

        return $job;
    }

    /**
     * @param string $queue
     *
     * @return string
     */
    public function getJobClassName($queue)
    {
        if (!isset($this->queues[$queue])) {
            return null;
        }

        $queueProperties = $this->queues[$queue];

        return $queueProperties[self::KEY_JOB_CLASS_NAME];
    }
}
