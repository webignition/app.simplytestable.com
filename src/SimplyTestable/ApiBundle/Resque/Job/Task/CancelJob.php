<?php

namespace SimplyTestable\ApiBundle\Resque\Job\Task;

use SimplyTestable\ApiBundle\Command\Task\Cancel\Command;
use SimplyTestable\ApiBundle\Resque\Job\CommandJob;

class CancelJob extends CommandJob {

    const QUEUE_NAME = 'task-cancel';

    protected function getQueueName() {
        return self::QUEUE_NAME;
    }

    protected function getCommand() {
        return new Command();
    }

    protected function getCommandArgs() {
        return [
            'id' => $this->args['id']
        ];
    }
}