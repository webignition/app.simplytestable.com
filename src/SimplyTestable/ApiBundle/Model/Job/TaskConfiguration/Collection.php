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

}