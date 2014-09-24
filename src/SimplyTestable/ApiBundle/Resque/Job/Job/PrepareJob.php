<?php

namespace SimplyTestable\ApiBundle\Resque\Job\Job;

use SimplyTestable\ApiBundle\Command\Job\PrepareCommand;
use SimplyTestable\ApiBundle\Resque\Job\CommandJob;

class PrepareJob extends CommandJob {
    
    const QUEUE_NAME = 'job-prepare';

    protected function getQueueName() {
        return self::QUEUE_NAME;
    }

    protected function getCommand() {
        return new PrepareCommand();
    }

    protected function getCommandArgs() {
        return [
            'id' => $this->args['id']
        ];
    }
}