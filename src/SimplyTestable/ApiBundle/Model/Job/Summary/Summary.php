<?php

namespace SimplyTestable\ApiBundle\Model\Job\Summary;

use SimplyTestable\ApiBundle\Entity\Job\Ammendment;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Job\RejectionReason;
use SimplyTestable\ApiBundle\Entity\User;

class Summary implements \JsonSerializable
{
    /**
     * @var Job
     */
    private $job;

    /**
     * @var int
     */
    private $taskCount;

    /**
     * @var array
     */
    private $taskCountByState;

    /**
     * @var int
     */
    private $tasksWithErrorsCount;

    /**
     * @var int
     */
    private $tasksWithWarningsCount;

    /**
     * @var int
     */
    private $skippedTaskCount;

    /**
     * @var int
     */
    private $cancelledTaskCount;

    /**
     * @var bool
     */
    private $isPublic;

    /**
     * @var int
     */
    private $errorCount;

    /**
     * @var int
     */
    private $warningCount;

    /**
     * @var User[]
     */
    private $owners;

    /**
     * @var RejectionReason
     */
    private $rejectionReason;

    /**
     * @var Ammendment[]
     */
    private $ammendments;

    /**
     * @var CrawlSummary
     */
    private $crawlSummary;

    /**
     * @param Job $job
     * @param int $taskCount
     * @param array $taskCountByState
     * @param int $tasksWithErrorsCount
     * @param int $tasksWithWarningsCount
     * @param int $skippedTaskCount
     * @param int $cancelledTaskCount
     * @param bool $isPublic
     * @param int $errorCount
     * @param int $warningCount
     * @param User[] $owners
     */
    public function __construct(
        Job $job,
        $taskCount,
        $taskCountByState,
        $tasksWithErrorsCount,
        $tasksWithWarningsCount,
        $skippedTaskCount,
        $cancelledTaskCount,
        $isPublic,
        $errorCount,
        $warningCount,
        $owners
    ) {
        $this->job = $job;
        $this->taskCount = $taskCount;
        $this->taskCountByState = $taskCountByState;
        $this->tasksWithErrorsCount = $tasksWithErrorsCount;
        $this->tasksWithWarningsCount = $tasksWithWarningsCount;
        $this->skippedTaskCount = $skippedTaskCount;
        $this->cancelledTaskCount = $cancelledTaskCount;
        $this->isPublic = $isPublic;
        $this->errorCount = $errorCount;
        $this->warningCount = $warningCount;
        $this->owners = $owners;
    }

    /**
     * @param RejectionReason $rejectionReason
     */
    public function setRejectionReason(RejectionReason $rejectionReason)
    {
        $this->rejectionReason = $rejectionReason;
    }

    /**
     * @param Ammendment[] $ammendments
     */
    public function setAmmendments($ammendments)
    {
        $this->ammendments = $ammendments;
    }

    /**
     * @param CrawlSummary $crawlSummary
     */
    public function setCrawlSummary(CrawlSummary $crawlSummary)
    {
        $this->crawlSummary = $crawlSummary;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $serialisedOwners = [];

        foreach ($this->owners as $owner) {
            $serialisedOwners[] = $owner->getUsername();
        }

        $serializedJobSummary = array_merge($this->job->jsonSerialize(), [
            'task_count' => $this->taskCount,
            'task_count_by_state' => $this->taskCountByState,
            'errored_task_count' => $this->tasksWithErrorsCount,
            'warninged_task_count' => $this->tasksWithWarningsCount,
            'skipped_task_count' => $this->skippedTaskCount,
            'cancelled_task_count' => $this->cancelledTaskCount,
            'is_public' => $this->isPublic,
            'error_count' => $this->errorCount,
            'warning_count' => $this->warningCount,
            'owners' => $serialisedOwners,
        ]);

        if (!empty($this->rejectionReason)) {
            $serializedJobSummary['rejection'] = $this->rejectionReason->jsonSerialize();
        }

        if (!empty($this->ammendments)) {
            $serializedAmmendments = [];

            foreach ($this->ammendments as $ammendment) {
                $serializedAmmendments[] = $ammendment->jsonSerialize();
            }

            $serializedJobSummary['ammendments'] = $serializedAmmendments;
        }

        if (!empty($this->crawlSummary)) {
            $serializedJobSummary['crawl'] = $this->crawlSummary->jsonSerialize();
        }

        return $serializedJobSummary;
    }
}
