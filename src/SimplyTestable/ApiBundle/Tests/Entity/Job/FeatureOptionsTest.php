<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\Account\Plan;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Job\FeatureOptions;
use SimplyTestable\ApiBundle\Entity\Job\Ammendment;

class FeatureOptionsTest extends BaseSimplyTestableTestCase {

    public function testPersistWithEmptyOptions() {        
        $canonicalUrl = 'http://example.com/';        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));     
        
        $featureOptions = new FeatureOptions();        
        $featureOptions->setJob($job);

        $this->getEntityManager()->persist($featureOptions);        
        $this->getEntityManager()->flush();        
  
        $this->assertNotNull($featureOptions->getId());               
    }    
    
    public function testPersistWithNonEmptyOptions() {        
        $canonicalUrl = 'http://example.com/';        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));
      
        $featureOptions = new FeatureOptions();        
        $featureOptions->setOtions(json_encode(array('foo' => 'bar')));
        $featureOptions->setJob($job);

        $this->getEntityManager()->persist($featureOptions);        
        $this->getEntityManager()->flush();
  
        $this->assertNotNull($featureOptions->getId());               
    }  
}
