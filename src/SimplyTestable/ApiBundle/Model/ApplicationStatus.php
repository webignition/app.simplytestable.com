<?php

namespace SimplyTestable\ApiBundle\Model;

use SimplyTestable\ApiBundle\Entity\Worker;

class ApplicationStatus implements \JsonSerializable
{
    /**
     * @var string
     */
    private $state;

    /**
     * @var Worker[]
     */
    private $workers;

    /**
     * @var string
     */
    private $version;

    /**
     * @var int
     */
    private $taskThroughputPerMinute;

    /**
     * @var int
     */
    private $inProgressJobCount;

    /**
     * @param string $state
     * @param Worker[] $workers
     * @param string $version
     * @param int $taskThroughputPerMinute
     * @param int $inProgressJobCount
     */
    public function __construct(
        $state,
        $workers,
        $version,
        $taskThroughputPerMinute,
        $inProgressJobCount
    ) {
        $this->state = $state;
        $this->workers = $workers;
        $this->version = $version;
        $this->taskThroughputPerMinute = $taskThroughputPerMinute;
        $this->inProgressJobCount = $inProgressJobCount;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $workerData = [];

        foreach ($this->workers as $worker) {
            /* @var Worker $worker */

            $workerData[] = $worker->jsonSerialize();
        }

        return [
            'state' => $this->state,
            'workers' => $workerData,
            'version' => $this->version,
            'task_throughput_per_minute' => $this->taskThroughputPerMinute,
            'in_progress_job_count' => $this->inProgressJobCount,
        ];
    }
}
