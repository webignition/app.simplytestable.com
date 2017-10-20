<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\User;

use SimplyTestable\ApiBundle\Controller\UserController;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

abstract class AbstractUserControllerTest extends BaseSimplyTestableTestCase
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
        $this->userController->setContainer($this->container);
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
