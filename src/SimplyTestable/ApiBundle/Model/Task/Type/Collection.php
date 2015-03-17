<?php

namespace SimplyTestable\ApiBundle\Model\Task\Type;

use  SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;

class Collection {

    /**
     * @var TaskType[]
     */
    private $collection = [];


    /**
     * @param TaskType $taskType
     */
    public function add(TaskType $taskType) {
        if (!$this->contains($taskType)) {
            $this->collection[] = $taskType;
        }
    }


    /**
     * @return $taskType[]
     */
    public function get() {
        return $this->collection;
    }


    /**
     * @param $taskType $taskConfiguration
     * @return bool
     */
    public function contains(TaskType $taskType) {
        foreach ($this->collection as $comparator) {
            if ($comparator->equals($taskType)) {
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

        /* @var $taskType TaskType */
        foreach ($this->get() as $taskType) {
            if ($comparator->contains($taskType)) {
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

}