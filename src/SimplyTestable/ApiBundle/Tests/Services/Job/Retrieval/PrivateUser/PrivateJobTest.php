<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Retrieval\PublicUser;

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

        $this->job = $this->createJobFactory()->create(
            'full site',
            'http://example.com/',
            ['html validation',],
            [],
            [],
            $user
        );

        $this->getJobRetrievalService()->setUser($user);
    }

    public function testRetrieve()
    {
        $this->assertEquals(
            $this->job->getId(),
            $this->getJobRetrievalService()->retrieve($this->job->getId())->getId()
        );
    }
}
