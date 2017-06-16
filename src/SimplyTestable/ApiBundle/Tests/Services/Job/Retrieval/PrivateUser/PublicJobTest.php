<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Retrieval\PublicUser;

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

        $this->job = $this->createJobFactory()->create(
            'full site',
            'http://example.com/',
            ['html validation',],
            [],
            [],
            $this->getUserService()->getPublicUser()
        );

        $this->getJobRetrievalService()->setUser($this->createAndActivateUser('user@example.com'));
    }


    public function testRetrieve()
    {
        $this->assertEquals(
            $this->job->getId(),
            $this->getJobRetrievalService()->retrieve($this->job->getId())->getId()
        );
    }
}
