<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Start\StartAction;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Services\JobService;

class UnroutableWebsiteTest extends RedirectResponseTest {


    /**
     * @var Job
     */
    private $job;

    protected function setUp() {
        parent::setUp();

        $this->job = $this->getJobFromResponse($this->response);
    }


    protected function getCanonicalUrl() {
        return 'http://foo';
    }


    public function testIsRejected() {
        $this->assertEquals(JobService::REJECTED_STATE, $this->job->getState()->getName());
    }
}