<?php

namespace SimplyTestable\ApiBundle\Resque\Job\Task;

use SimplyTestable\ApiBundle\Command\Task\Cancel\CollectionCommand;
use SimplyTestable\ApiBundle\Resque\Job\CommandJob;

class CancelCollectionJob extends CommandJob {

    const QUEUE_NAME = 'task-cancel-collection';

    protected function getQueueName() {
        return self::QUEUE_NAME;
    }

    protected function getCommand() {
        return new CollectionCommand();
    }

    protected function getCommandArgs() {
        return [
            'ids' => $this->args['ids']
        ];
    }
}