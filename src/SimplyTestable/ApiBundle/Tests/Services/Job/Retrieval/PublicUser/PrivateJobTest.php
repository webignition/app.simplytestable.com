<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Retrieval\PrivateUser;

use SimplyTestable\ApiBundle\Exception\Services\Job\RetrievalServiceException;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Services\Job\Retrieval\ServiceTest;
use SimplyTestable\ApiBundle\Entity\Job\Job;

class PrivateJobTest extends ServiceTest
{
    /**
     * @var Job
     */
    private $job;

    public function setUp()
    {
        parent::setUp();

        $user = $this->createAndActivateUser('user@example.com');

        $this->job = $this->createJobFactory()->create([
            JobFactory::KEY_USER => $user,
        ]);

        $this->getJobRetrievalService()->setUser($this->getUserService()->getPublicUser());
    }

    public function testRetrieveThrowsJobRetrievalServiceException()
    {
        $this->setExpectedException(
            RetrievalServiceException::class,
            'Not authorised',
            3
        );

        $this->getJobRetrievalService()->retrieve($this->job->getId())->getId();
    }
}
