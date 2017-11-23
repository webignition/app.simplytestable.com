<?php

namespace Tests\ApiBundle\Functional\Controller\User;

use SimplyTestable\ApiBundle\Controller\UserController;
use SimplyTestable\ApiBundle\Services\UserService;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

abstract class AbstractUserControllerTest extends AbstractBaseTestCase
{
    /**
     * @var UserController
     */
    protected $userController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userController = new UserController(
            $this->container->get(UserService::class)
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
