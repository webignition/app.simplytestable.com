<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Retrieval;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Exception\Services\Job\RetrievalServiceException;

class ExceptionCasesTest extends ServiceTest
{
    /**
     * @var Job
     */
    private $job;

    public function setUp()
    {
        parent::setUp();
        $this->job = $this->createJobFactory()->create();
    }

    public function testNoUserThrowsJobRetrievalServiceException()
    {
        $this->setExpectedException(
            RetrievalServiceException::class,
            'User not set',
            1
        );

        $this->getJobRetrievalService()->retrieve($this->job->getId());
    }

    public function testJobNotFoundThrowsJobRetrievalServiceException()
    {
        $this->getJobRetrievalService()->setUser($this->job->getUser());

        $this->setExpectedException(
            RetrievalServiceException::class,
            'Job [0] not found',
            2
        );

        $this->getJobRetrievalService()->retrieve(0);
    }
}
