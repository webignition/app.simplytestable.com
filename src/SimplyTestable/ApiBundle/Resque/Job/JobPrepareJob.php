<?php

namespace SimplyTestable\ApiBundle\Resque\Job;

use SimplyTestable\ApiBundle\Exception\JobPrepareException;

class JobPrepareJob extends AbstractJob {    
    
    const QUEUE_NAME = 'job-prepare';
    
    public function __constuct() {
        $this->setQueue(self::QUEUE_NAME);
    }
    
    public function perform() {
        $output = array();
        $returnValue = null;
        
        exec('php app/console simplytestable:job:prepare ' . $this->args['id'], $output, $returnValue);
        
        if ($returnValue !== 0) {
            throw new JobPrepareException(implode("\n", $output), $returnValue);
        }
    }    
}