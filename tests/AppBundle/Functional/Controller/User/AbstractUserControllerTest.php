<?php

namespace Tests\AppBundle\Functional\Controller\User;

use AppBundle\Controller\UserController;
use AppBundle\Services\UserService;
use Tests\AppBundle\Functional\Controller\AbstractControllerTest;

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
