<?php

namespace SimplyTestable\ApiBundle\Resque\Job\Worker;

use SimplyTestable\ApiBundle\Command\WorkerActivateVerifyCommand;
use SimplyTestable\ApiBundle\Resque\Job\CommandJob;

class ActivateVerifyJob extends CommandJob {

    const QUEUE_NAME = 'worker-activate-verify';

    protected function getQueueName() {
        return self::QUEUE_NAME;
    }

    public function getCommand() {
        return new WorkerActivateVerifyCommand();
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