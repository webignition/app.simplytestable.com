<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Retrieval\PrivateUser;

use SimplyTestable\ApiBundle\Tests\Services\Job\Retrieval\ServiceTest;
use SimplyTestable\ApiBundle\Entity\Job\Job;

class PublicJobTest extends ServiceTest
{
    /**
     * @var Job
     */
    private $job;

    public function setUp()
    {
        parent::setUp();

        $this->job = $this->createJobFactory()->create();

        $this->getJobRetrievalService()->setUser($this->job->getUser());
    }

    public function testRetrieve()
    {
        $this->assertEquals(
            $this->job->getId(),
            $this->getJobRetrievalService()->retrieve($this->job->getId())->getId()
        );
    }
}
