<?php

namespace App\Tests\Functional\Controller\User;

use App\Controller\UserController;
use App\Services\UserService;
use App\Tests\Functional\Controller\AbstractControllerTest;

abstract class AbstractUserControllerTest extends AbstractControllerTest
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
            self::$container->get(UserService::class)
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
