<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare\Cookies;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
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
        $user = $this->getTestUser();
        $this->getUserService()->setUser($user);

        $jobFactory = $this->createJobFactory();
        $this->job = $jobFactory->create([
            JobFactory::KEY_USER => $user,
            JobFactory::KEY_PARAMETERS => [
                'cookies' => $this->cookies
            ],
        ]);

        $jobFactory->resolve($this->job);
    }
}
