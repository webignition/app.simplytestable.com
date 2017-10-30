<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job;

use SimplyTestable\ApiBundle\Controller\Job\JobController;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\AbstractBaseTestCase;

abstract class AbstractJobControllerTest extends AbstractBaseTestCase
{
    /**
     * @var UserFactory
     */
    protected $userFactory;

    /**
     * @var JobFactory
     */
    protected $jobFactory;

    /**
     * @var JobController
     */
    protected $jobController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobController = new JobController();
        $this->jobController->setContainer($this->container);

        $this->userFactory = new UserFactory($this->container);
        $this->jobFactory = new JobFactory($this->container);
    }
}
