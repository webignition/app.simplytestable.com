<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\AmmendmentCases;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

/**
 * Test preparing a full-site test with the public user for a site with
 * more urls than is permitted creates an url count ammendment
 */
class UrlCountTest extends BaseSimplyTestableTestCase {    
    
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
    
    
    public function testHasAmmendment() {
        $this->assertEquals(1, $this->job->getAmmendments()->count());
    }
    
    
    public function testAmmendmentReason() {
        $this->assertEquals('plan-url-limit-reached:discovered-url-count-11', $this->getAmmendment()->getReason());
    }    
   
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Entity\Job\Ammendment
     */
    private function getAmmendment() {
        return $this->job->getAmmendments()->first();
    }
}