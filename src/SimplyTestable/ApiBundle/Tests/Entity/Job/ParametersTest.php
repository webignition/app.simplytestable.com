<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\Account\Plan;

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
        $this->getJobService()->getEntityManager()->clear();

        $this->assertEquals('{"foo":"bar"}', $this->getJobService()->getById($jobId)->getParameters());
    } 
}
