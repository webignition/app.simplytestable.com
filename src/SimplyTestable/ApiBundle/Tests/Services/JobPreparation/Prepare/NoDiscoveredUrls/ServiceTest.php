<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare\NoDiscoveredUrls;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class ServiceTest extends BaseSimplyTestableTestCase {    
    
    const CANONICAL_URL = 'http://example.com';    

    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\Job\Job
     */
    private $job;
    
    public function setUp() {
        parent::setUp();
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(). '/HttpResponses'));
        $this->job = $this->getJobService()->getById($this->createJobAndGetId(self::CANONICAL_URL));
        $this->getJobPreparationService()->prepare($this->job);
    }
    
    
    public function testStateIsFailedNoSitemap() {
        $this->assertEquals($this->getJobService()->getFailedNoSitemapState(), $this->job->getState());
    }
    
    public function testHasNoTasks() {
        $this->assertEquals(0, $this->job->getTasks()->count());
    }

}
