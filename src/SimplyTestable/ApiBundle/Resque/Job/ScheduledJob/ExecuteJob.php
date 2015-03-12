<?php

namespace SimplyTestable\ApiBundle\Resque\Job\ScheduledJob;

use SimplyTestable\ApiBundle\Command\ScheduledJob\ExecuteCommand;
use SimplyTestable\ApiBundle\Resque\Job\CommandJob;

class ExecuteJob extends CommandJob {
    
    const QUEUE_NAME = 'scheduledjob-execute';

    protected function getQueueName() {
        return self::QUEUE_NAME;
    }

    public function getCommand() {
        return new ExecuteCommand();
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