<?php

namespace SimplyTestable\ApiBundle\Resque\Job;

use SimplyTestable\ApiBundle\Exception\TaskAssignException;

class TaskAssignmentSelectionJob extends CommandLineJob {    
    
    const QUEUE_NAME = 'task-assignment-selection';
    const COMMAND = 'php app/console simplytestable:task:assign:select';
    
    protected function getQueueName() {
        return self::QUEUE_NAME;
    }
    
    protected function getArgumentOrder() {
    }
    
    protected function getCommand() {
        return self::COMMAND;
    }
    
    protected function failureHandler($output, $returnValue) {
        throw new TaskAssignException(implode("\n", $output), $returnValue);
    }   
}