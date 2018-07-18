<?php

namespace AppBundle\Resque\Job;

use ResqueBundle\Resque\ContainerAwareJob;

abstract class Job extends ContainerAwareJob
{
    abstract protected function getQueueName();

    public function __construct($args = [])
    {
        $this->args = $args;
        $this->setQueue($this->getQueueName());
    }

    public function setQueue($queue)
    {
        $this->queue = $queue;
    }
}
