<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Start\StartAction;

use SimplyTestable\ApiBundle\Entity\Job\Job;

class UnroutableWebsiteTest extends RedirectResponseTest {


    /**
     * @var Job
     */
    private $job;

    public function setUp() {
        parent::setUp();

        $this->job = $this->getJobService()->getById($this->getJobIdFromUrl($this->response->getTargetUrl()));
    }


    protected function getCanonicalUrl() {
        return 'http://foo';
    }


    public function testIsRejected() {
        $this->assertEquals($this->getJobService()->getRejectedState(), $this->job->getState());
    }
}