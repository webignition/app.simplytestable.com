<?php

namespace SimplyTestable\ApiBundle\Resque\Job\Worker\Tasks;

use SimplyTestable\ApiBundle\Command\Worker\TaskNotificationCommand;
use SimplyTestable\ApiBundle\Resque\Job\CommandJob;

class NotifyJob extends CommandJob {

    const QUEUE_NAME = 'tasks-notify';

    protected function getQueueName() {
        return self::QUEUE_NAME;
    }

    public function getCommand() {
        return new TaskNotificationCommand();
    }

    protected function getCommandArgs() {
        return [];
    }

    protected function getIdentifier() {
        return 'default';
    }
}