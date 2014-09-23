<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\Job;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class ParametersTest extends BaseSimplyTestableTestCase {

    public function testSetPersistGetParameters() {
        $canonicalUrl = 'http://example.com/';  
        $jobId = $this->createJobAndGetId($canonicalUrl);
        $job = $this->getJobService()->getById($jobId);        
        
        $job->setParameters(json_encode(array(
            'foo' => 'bar'
        )));
        
        $this->getJobService()->persistAndFlush($job);
        $this->getJobService()->getManager()->clear();

        $this->assertEquals('{"foo":"bar"}', $this->getJobService()->getById($jobId)->getParameters());
    } 
    
    public function testUtf8() {
        $key = 'key-ɸ';
        $value = 'value-ɸ';
        
        $canonicalUrl = 'http://example.com/';  
        $jobId = $this->createJobAndGetId($canonicalUrl);
        $job = $this->getJobService()->getById($jobId);        
        
        $job->setParameters(json_encode(array(
            $key => $value
        )));
        
        $this->getJobService()->persistAndFlush($job);
        $this->getJobService()->getManager()->clear();

        $this->assertEquals('{"key-\u0278":"value-\u0278"}', $this->getJobService()->getById($jobId)->getParameters());
    }     
}
