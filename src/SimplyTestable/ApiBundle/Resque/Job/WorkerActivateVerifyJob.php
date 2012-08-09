<?php

namespace SimplyTestable\ApiBundle\Resque\Job;

use SimplyTestable\ApiBundle\Exception\WorkerActivateVerifyException;

class WorkerActivateVerifyJob extends CommandLineJob {    
    
    const QUEUE_NAME = 'worker-activate-verify';
    const COMMAND = 'php app/console simplytestable:worker:activate:verify';
    
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
        throw new WorkerActivateVerifyException(implode("\n", $output), $returnValue);
    }   
}