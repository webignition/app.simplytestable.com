<?php

namespace SimplyTestable\ApiBundle\Resque\Job\Job;

use SimplyTestable\ApiBundle\Command\Job\ResolveWebsiteCommand;
use SimplyTestable\ApiBundle\Resque\Job\CommandJob;

class ResolveJob extends CommandJob {

    const QUEUE_NAME = 'job-resolve';

    protected function getQueueName() {
        return self::QUEUE_NAME;
    }

    protected function getCommand() {
        return new ResolveWebsiteCommand();
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