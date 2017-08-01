<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\JobPreparation\Prepare\Cookies;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

abstract class ServiceTest extends BaseSimplyTestableTestCase
{
    /**
     * @var array
     */
    protected $cookies = array(
        array(
            'domain' => '.example.com',
            'name' => 'foo',
            'value' => 'bar'
        )
    );

    /**
     * @var Job
     */
    protected $job;

    public function setUp()
    {
        parent::setUp();

        $this->queueResolveHttpFixture();
        $userFactory = new UserFactory($this->container);

        $user = $userFactory->create();
        $this->getUserService()->setUser($user);

        $jobFactory = new JobFactory($this->container);
        $this->job = $jobFactory->create([
            JobFactory::KEY_USER => $user,
            JobFactory::KEY_PARAMETERS => [
                'cookies' => $this->cookies
            ],
        ]);

        $jobFactory->resolve($this->job);
    }
}
