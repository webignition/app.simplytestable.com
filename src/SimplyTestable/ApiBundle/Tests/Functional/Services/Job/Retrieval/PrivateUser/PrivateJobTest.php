<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Retrieval\PublicUser;

use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Retrieval\ServiceTest;
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
