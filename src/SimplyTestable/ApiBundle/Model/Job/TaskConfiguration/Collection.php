<?php

namespace SimplyTestable\ApiBundle\Model\Job\TaskConfiguration;

use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Model\Task\Type\Collection as TaskTypeCollection;

class Collection
{
    /**
     * @var TaskConfiguration[]
     */
    private $collection = [];

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
    public function get()
    {
        return $this->collection;
    }

    /**
     * @return TaskConfiguration[]
     */
    public function getEnabled()
    {
        $collection = [];

        foreach ($this->get() as $taskConfiguration) {
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
        $comparatorCollection = $comparator->get();
        $thisCollectionCount = count($this->collection);

        if ($thisCollectionCount !== count($comparatorCollection)) {
            return false;
        }

        $matchCount = 0;

        /* @var $taskConfiguration TaskConfiguration */
        foreach ($this->get() as $taskConfiguration) {
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

        foreach ($this->get() as $taskConfiguration) {
            $taskTypes->add($taskConfiguration->getType());
        }

        return $taskTypes;
    }
}
