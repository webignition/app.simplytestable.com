<?php

namespace Tests\ApiBundle\Functional\Controller\Job\Job;

use SimplyTestable\ApiBundle\Controller\Job\JobController;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

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
