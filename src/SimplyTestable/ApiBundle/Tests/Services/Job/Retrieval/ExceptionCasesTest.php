<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Retrieval;

use SimplyTestable\ApiBundle\Entity\Job\Job;

class ExceptionCasesTest extends ServiceTest {

    const CANONICAL_URL = 'http://example.com/';

    /**
     * @var Job
     */
    private $job;

    public function setUp() {
        parent::setUp();

        $response = $this->getJobStartController('startAction')->startAction(self::CANONICAL_URL);
        $this->job = $this->getJobService()->getById($this->getJobIdFromUrl($response->getTargetUrl()));
    }


    public function testNoUserThrowsJobRetrievalServiceException() {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Job\RetrievalServiceException',
            'User not set',
            1
        );

        $this->getJobRetrievalService()->retrieve($this->job->getId());
    }


    public function testJobNotFoundThrowsJobRetrievalServiceException() {
        $this->getJobRetrievalService()->setUser($this->job->getUser());

        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Job\RetrievalServiceException',
            'Job [0] not found',
            2
        );

        $this->getJobRetrievalService()->retrieve(0);
    }

}