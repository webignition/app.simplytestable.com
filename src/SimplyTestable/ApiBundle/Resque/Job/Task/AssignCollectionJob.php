<?php

namespace SimplyTestable\ApiBundle\Resque\Job\Task;

use SimplyTestable\ApiBundle\Command\Task\Assign\CollectionCommand;
use SimplyTestable\ApiBundle\Resque\Job\CommandJob;

class AssignCollectionJob extends CommandJob {

    const QUEUE_NAME = 'task-assign-collection';

    protected function getQueueName() {
        return self::QUEUE_NAME;
    }

    public function getCommand() {
        return new CollectionCommand();
    }

    protected function getCommandArgs() {
        if (isset($this->args['worker'])) {
            return [
                'ids' => $this->args['ids'],
                'worker' => $this->args['worker']
            ];
        }

        return [
            'ids' => $this->args['ids']
        ];
    }

    protected function getIdentifier() {
        return $this->args['ids'];
    }
}