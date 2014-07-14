<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Retrieval\PublicUser;

use SimplyTestable\ApiBundle\Tests\Services\Job\Retrieval\ServiceTest;
use SimplyTestable\ApiBundle\Entity\Job\Job;

class PrivateJobTest extends ServiceTest {

    const CANONICAL_URL = 'http://example.com/';

    /**
     * @var Job
     */
    private $job;

    public function setUp() {
        parent::setUp();

        $user = $this->createAndActivateUser('user@example.com');

        $this->job = $this->getJobService()->getById($this->getJobIdFromUrl($this->createJob(self::CANONICAL_URL, $user->getEmail())->getTargetUrl()));

        $this->getJobRetrievalService()->setUser($user);
    }


    public function testRetrieve() {
        $this->assertEquals($this->job->getId(), $this->getJobRetrievalService()->retrieve($this->job->getId())->getId());
    }

}