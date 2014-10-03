<?php

namespace SimplyTestable\ApiBundle\Resque\Job\Task\Assign;

use SimplyTestable\ApiBundle\Command\Task\Assign\JobFirstSetCommand;
use SimplyTestable\ApiBundle\Resque\Job\CommandJob;

class JobFirstSetJob extends CommandJob {

    const QUEUE_NAME = 'task-assign-jobfirstset';

    protected function getQueueName() {
        return self::QUEUE_NAME;
    }

    protected function getCommand() {
        return new JobFirstSetCommand();
    }

    protected function getCommandArgs() {
        return [
            'id' => $this->args['id']
        ];
    }

    protected function getIdentifier() {
        return $this->args['id'];
    }
}