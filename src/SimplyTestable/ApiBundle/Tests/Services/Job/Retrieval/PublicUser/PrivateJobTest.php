<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Retrieval\PrivateUser;

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

        $this->getJobRetrievalService()->setUser($this->getUserService()->getPublicUser());
    }


    public function testRetrieveThrowsJobRetrievalServiceException() {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Job\RetrievalServiceException',
            'Not authorised',
            3
        );

        $this->assertEquals($this->job->getId(), $this->getJobRetrievalService()->retrieve($this->job->getId())->getId());
    }

}