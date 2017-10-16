<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller;

use SimplyTestable\ApiBundle\Controller\UserController;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class IsEnabledTest extends BaseSimplyTestableTestCase
{
    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * @var UserController
     */
    private $userController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userFactory = new UserFactory($this->container);
        $this->userController = new UserController();
        $this->userController->setContainer($this->container);
    }

    public function testExistsWithNotEnabledUser() {
        $user = $this->userFactory->create();

        $response = $this->userController->existsAction($user->getEmail());

        $this->assertEquals(200, $response->getStatusCode());
    }


    public function testExistsWithEnabledUser() {
        $user = $this->userFactory->createAndActivateUser();

        $response = $this->userController->existsAction($user->getEmail());

        $this->assertEquals(200, $response->getStatusCode());
    }


    public function testExistsWithNonExistentUser() {
        $email = 'user1@example.com';

        try {
            $this->userController->existsAction($email);
            $this->fail('Attempt to check existence for non-existent user did not generate HTTP 404');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(404, $exception->getStatusCode());
        }
    }
}


