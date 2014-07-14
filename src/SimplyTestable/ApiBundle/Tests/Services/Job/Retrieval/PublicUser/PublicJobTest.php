<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Retrieval\PrivateUser;

use SimplyTestable\ApiBundle\Tests\Services\Job\Retrieval\ServiceTest;
use SimplyTestable\ApiBundle\Entity\Job\Job;

class PublicJobTest extends ServiceTest {

    const CANONICAL_URL = 'http://example.com/';

    /**
     * @var Job
     */
    private $job;

    public function setUp() {
        parent::setUp();

        $response = $this->getJobStartController('startAction')->startAction(self::CANONICAL_URL);
        $this->job = $this->getJobService()->getById($this->getJobIdFromUrl($response->getTargetUrl()));

        $this->getJobRetrievalService()->setUser($this->job->getUser());
    }


    public function testRetrieve() {
        $this->assertEquals($this->job->getId(), $this->getJobRetrievalService()->retrieve($this->job->getId())->getId());
    }

}