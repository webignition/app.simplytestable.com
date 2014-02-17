<?php

namespace SimplyTestable\ApiBundle\Resque\Job\Stripe;

use SimplyTestable\ApiBundle\Exception\TaskAssignException;
use SimplyTestable\ApiBundle\Resque\Job\CommandLineJob;

class ProcessEventJob extends CommandLineJob {    
    
    const QUEUE_NAME = 'stripe-event';
    const COMMAND = 'php app/console simplytestable:stripe:event:process';
    
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