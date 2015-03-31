<?php

namespace SimplyTestable\ApiBundle\Model\Job\TaskConfiguration;

use Proxies\__CG__\SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use  SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;
use SimplyTestable\ApiBundle\Model\Task\Type\Collection as TaskTypeCollection;

class Collection {

    /**
     * @var TaskConfiguration[]
     */
    private $collection = [];


    /**
     * @param TaskConfiguration $taskConfiguration
     */
    public function add(TaskConfiguration $taskConfiguration) {
        if (!$this->contains($taskConfiguration)) {
            $this->collection[] = $taskConfiguration;
        }
    }


    /**
     * @return TaskConfiguration[]
     */
    public function get() {
        return $this->collection;
    }


    /**
     * @return TaskConfiguration[]
     */
    public function getEnabled() {
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
     * @return bool
     */
    public function contains(TaskConfiguration $taskConfiguration) {
        foreach ($this->collection as $comparator) {
            if ($comparator->hasMatchingTypeAndOptions($taskConfiguration) && $comparator->getIsEnabled() == $taskConfiguration->getIsEnabled()) {
                return true;
            }
        }

        return false;
    }


    /**
     * @return int
     */
    public function count() {
        return count($this->collection);
    }


    /**
     * @return bool
     */
    public function isEmpty() {
        return $this->count() == 0;
    }


    /**
     * @param Collection $comparator
     * @return bool
     */
    public function equals(Collection $comparator) {
        if ($this->count() != $comparator->count()) {
            return false;
        }

        $matchCount = 0;

        /* @var $taskConfiguration TaskConfiguration */
        foreach ($this->get() as $taskConfiguration) {
            if ($comparator->contains($taskConfiguration)) {
                $matchCount++;
            }
        }

        return $matchCount == $this->count();
    }


    /**
     * @return $this
     */
    public function clear() {
        $this->collection = [];
        return $this;
    }


    /**
     * @return TaskTypeCollection
     */
    public function getTaskTypes() {
        $taskTypes = new TaskTypeCollection();

        foreach ($this->get() as $taskConfiguration) {
            $taskTypes->add($taskConfiguration->getType());
        }

        return $taskTypes;
    }

}