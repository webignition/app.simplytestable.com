<?php

namespace SimplyTestable\ApiBundle\Model\Job\Configuration;

use SimplyTestable\ApiBundle\Entity\Job\Configuration;
use SimplyTestable\ApiBundle\Entity\User;

class Collection {

    /**
     * @var Configuration[]
     */
    private $collection = [];


    /**
     * @param Configuration $jobConfiguration
     */
    public function add(Configuration $jobConfiguration) {
        if (!$this->contains($jobConfiguration)) {
            $this->collection[] = $jobConfiguration;
        }
    }


    /**
     * @return Configuration[]
     */
    public function get() {
        return $this->collection;
    }


    /**
     * @param Configuration $jobConfiguration
     * @return bool
     */
    public function contains(Configuration $jobConfiguration) {
        foreach ($this->collection as $comparator) {
            if ($comparator->matches($jobConfiguration)) {
                return true;
            }
        }

        return false;
    }


    /**
     * @param User $user
     */
    public function excludeUser(User $user) {
        $collection = $this->collection;
        $this->collection = [];

        foreach ($collection as $configuration) {
            if ($configuration->getUser() != $user) {
                $this->collection[] = $configuration;
            }
        }
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
     * @param string $label
     * @return bool
     */
    public function containsLabel($label) {
        foreach ($this->get() as $configuration) {
            if ($configuration->getLabel() == $label) {
                return true;
            }
        }

        return false;
    }


    /**
     * @param $stem
     * @return string
     */
    public function generateLabel($stem) {
        if (!$this->containsLabel($stem)) {
            return $stem;
        }

        $suffix = 2;
        $newLabel = $stem . "." . $suffix;

        while ($this->containsLabel($newLabel)) {
            $suffix++;
            $newLabel = $stem . "." . $suffix;
        }

        return $newLabel;
    }

}