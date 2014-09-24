<?php
namespace SimplyTestable\ApiBundle\Services\Resque;

use SimplyTestable\ApiBundle\Resque\Job\Job;

class JobFactoryService {

    
    private $jobClassMap;
    
    public function __construct($jobClassMap) {
        $this->jobClassMap = $jobClassMap;
    }
    
    
    /**
     *
     * @param string $queue
     * @return string
     */
    public function getJobClassName($queue) {
        return $this->jobClassMap[$queue];
    }


    /**
     * @param $queue
     * @param array $args
     * @return Job
     */
    public function create($queue, $args = []) {
        $className = $this->getJobClassName($queue);

        /* @var $job Job */
        $job = new $className($args);
        $job->setQueue($queue);

        return $job;
    }
}