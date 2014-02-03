<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\WebsiteResolution\MetaRedirect\SameUrl;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

abstract class SameUrlTest extends BaseSimplyTestableTestCase {    
    
    const SOURCE_URL = 'http://example.com/';
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\Job\Job
     */
    private $job;
    
    public function setUp() {
        parent::setUp();
        
        $this->setHttpFixtures($this->getTestHttpFixtures());
        
        $this->job = $this->getJobService()->getById($this->createJobAndGetId(self::SOURCE_URL)); 
        $this->getJobWebsiteResolutionService()->resolve($this->job);
        $this->assertEquals(self::SOURCE_URL, $this->job->getWebsite()->getCanonicalUrl());
    }
    
    abstract protected function getTestHttpFixtures();
}
