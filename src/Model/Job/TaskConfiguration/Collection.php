<?php

namespace App\Model\Job\TaskConfiguration;

use App\Entity\Job\TaskConfiguration;
use App\Model\Task\Type\Collection as TaskTypeCollection;

class Collection implements \Iterator, \Countable
{
    /**
     * @var TaskConfiguration[]
     */
    private $collection = [];

    private $position = 0;

    public function __construct()
    {
        $this->collection = [];
        $this->position = 0;
    }

    /**
     * \Iterator methods
     */
    public function rewind()
    {
        $this->position = 0;
    }

    public function current(): TaskConfiguration
    {
        return $this->collection[$this->position];
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        ++$this->position;
    }

    public function valid()
    {
        return isset($this->collection[$this->position]);
    }

    /**
     * \Countable methods
     */
    public function count(): int
    {
        return count($this->collection);
    }

    /**
     * @param TaskConfiguration $taskConfiguration
     */
    public function add(TaskConfiguration $taskConfiguration)
    {
        if (!$this->contains($taskConfiguration)) {
            $this->collection[] = $taskConfiguration;
        }
    }

    /**
     * @return TaskConfiguration[]
     */
    public function getEnabled()
    {
        $collection = [];

        foreach ($this as $taskConfiguration) {
            if ($taskConfiguration->getIsEnabled()) {
                $collection[] = $taskConfiguration;
            }
        }

        return $collection;
    }

    /**
     * @param TaskConfiguration $taskConfiguration
     *
     * @return bool
     */
    public function contains(TaskConfiguration $taskConfiguration)
    {
        foreach ($this->collection as $comparator) {
            $hasMatchingTypeAndOptions = $comparator->hasMatchingTypeAndOptions($taskConfiguration);
            $isEnabledMatches = $comparator->getIsEnabled() == $taskConfiguration->getIsEnabled();

            if ($hasMatchingTypeAndOptions && $isEnabledMatches) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->collection);
    }

    /**
     * @param Collection $comparator
     *
     * @return bool
     */
    public function equals(Collection $comparator)
    {
        $thisCollectionCount = count($this->collection);
        if (count($this) !== count($comparator)) {
            return false;
        }

        $matchCount = 0;

        foreach ($this as $taskConfiguration) {
            if ($comparator->contains($taskConfiguration)) {
                $matchCount++;
            }
        }

        return $matchCount === $thisCollectionCount;
    }

    /**
     * @return TaskTypeCollection
     */
    public function getTaskTypes()
    {
        $taskTypes = new TaskTypeCollection();

        foreach ($this as $taskConfiguration) {
            $taskTypes->add($taskConfiguration->getType());
        }

        return $taskTypes;
    }
}
