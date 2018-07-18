<?php

namespace AppBundle\Request\Task;

use AppBundle\Entity\State;
use AppBundle\Entity\Task\Task;
use webignition\InternetMediaType\InternetMediaType;

class CompleteRequest
{
    /**
     * @var \DateTime
     */
    private $endDateTime;

    /**
     * @var string
     */
    private $output;

    /**
     * @var InternetMediaType
     */
    private $contentType;

    /**
     * @var State
     */
    private $state;

    /**
     * @var int
     */
    private $errorCount;

    /**
     * @var int
     */
    private $warningCount;

    /**
     * @var Task[]
     */
    private $tasks;

    /**
     * @param \DateTime $endDateTime
     * @param string $output
     * @param InternetMediaType $contentType
     * @param State $state
     * @param int $errorCount
     * @param int $warningCount
     * @param Task[] $tasks
     */
    public function __construct(
        $endDateTime,
        $output,
        $contentType,
        $state,
        $errorCount,
        $warningCount,
        $tasks
    ) {
        $this->endDateTime = $endDateTime;
        $this->output = trim($output);
        $this->contentType = $contentType;
        $this->state = $state;
        $this->errorCount = $errorCount;
        $this->warningCount = $warningCount;
        $this->tasks = $tasks;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        if (!$this->endDateTime instanceof \DateTime) {
            return false;
        }

        if (!$this->contentType instanceof InternetMediaType) {
            return false;
        }

        if (!$this->state instanceof State) {
            return false;
        }

        return true;
    }

    /**
     * @return \DateTime
     */
    public function getEndDateTime()
    {
        return $this->endDateTime;
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @return State
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @return InternetMediaType
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * @return int
     */
    public function getErrorCount()
    {
        return $this->errorCount;
    }

    /**
     * @return int
     */
    public function getWarningCount()
    {
        return $this->warningCount;
    }

    /**
     * @return Task[]
     */
    public function getTasks()
    {
        return $this->tasks;
    }
}
