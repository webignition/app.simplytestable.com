<?php

namespace SimplyTestable\ApiBundle\Resque\Job\Task;

use SimplyTestable\ApiBundle\Command\Task\AssignmentSelectionCommand;
use SimplyTestable\ApiBundle\Resque\Job\CommandJob;

class AssignmentSelectionJob extends CommandJob {

    const QUEUE_NAME = 'task-assignment-selection';

    protected function getQueueName() {
        return self::QUEUE_NAME;
    }

    protected function getCommand() {
        return new AssignmentSelectionCommand();
    }

    protected function getCommandArgs() {
        return [];
    }
}