<?php

namespace SimplyTestable\ApiBundle\Resque\Job;

use SimplyTestable\ApiBundle\Exception\JobResolveException;

class JobResolveJob extends CommandLineJob {    
    
    const QUEUE_NAME = 'job-prepare';
    const COMMAND = 'php app/console simplytestable:job:resolve';
    
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
        throw new JobResolveException(implode("\n", $output), $returnValue);
    }   
}