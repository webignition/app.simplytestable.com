<?php
namespace SimplyTestable\ApiBundle\Services;



class ResqueJobFactoryService { 

    
    private $jobClassMap;
    
    public function __construct($jobClassMap) {
        $this->jobClassMap = $jobClassMap;
    }
    
    
    /**
     *
     * @param string $queueName
     * @return array 
     */
    public function getJobName($queueName) {
        return $this->jobClassMap[$queueName];
    }
}