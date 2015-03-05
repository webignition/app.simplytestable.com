<?php

namespace SimplyTestable\ApiBundle\Model\Job\TaskConfiguration;

use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;

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
     * @param TaskConfiguration $taskConfiguration
     * @return bool
     */
    public function contains(TaskConfiguration $taskConfiguration) {
        foreach ($this->collection as $comparator) {
            if ($comparator->hasMatchingTypeAndOptions($taskConfiguration)) {
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


        return true;
    }

}