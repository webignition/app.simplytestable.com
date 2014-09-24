<?php

namespace SimplyTestable\ApiBundle\Resque\Job;

use BCC\ResqueBundle\ContainerAwareJob as BaseJob;

abstract class Job extends BaseJob {

    abstract protected function getQueueName();

    public function __construct($args = []) {
        $this->args = $args;
        $this->setQueue($this->getQueueName());
    }


    public function setQueue($queue) {
        $this->queue = $queue;
    }

}