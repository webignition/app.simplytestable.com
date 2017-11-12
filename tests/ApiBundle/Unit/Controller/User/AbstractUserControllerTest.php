<?php

namespace Tests\ApiBundle\Unit\Controller\User;

use SimplyTestable\ApiBundle\Controller\UserController;

abstract class AbstractUserControllerTest extends \PHPUnit_Framework_TestCase
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

        $this->userController = new UserController();
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
