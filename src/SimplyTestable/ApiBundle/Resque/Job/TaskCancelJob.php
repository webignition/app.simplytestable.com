<?php

namespace SimplyTestable\ApiBundle\Resque\Job;

use SimplyTestable\ApiBundle\Exception\TaskAssignException;

class TaskCancelJob extends CommandLineJob {    
    
    const QUEUE_NAME = 'task-cancel';
    const COMMAND = 'php app/console simplytestable:task:cancel';
    
    protected function getQueueName() {
        return self::QUEUE_NAME;
    }
    
    protected function getArgumentOrder() {
        return array('id');
    }
    
    protected function getCommand() {
        return self::COMMAND;
    }
    
    protected function failureHandler($output, $returnValue) {
        throw new TaskAssignException(implode("\n", $output), $returnValue);
    }   
}