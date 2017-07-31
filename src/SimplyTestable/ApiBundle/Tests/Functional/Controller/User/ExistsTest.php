<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class ExistsTest extends BaseControllerJsonTestCase
{
    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userFactory = new UserFactory($this->container);
    }


    public function testExistsWithNotEnabledUser() {
        $user = $this->userFactory->create();

        $controller = $this->getUserController('existsAction');
        $response = $controller->existsAction($user->getEmail());

        $this->assertEquals(200, $response->getStatusCode());
    }


    public function testExistsWithEnabledUser() {
        $user = $this->userFactory->create();

        $controller = $this->getUserController('existsAction');
        $response = $controller->existsAction($user->getEmail());

        $this->assertEquals(200, $response->getStatusCode());
    }


    public function testExistsWithNonExistentUser() {
        $email = 'user1@example.com';

        try {
            $controller = $this->getUserController('existsAction');
            $controller->existsAction($email);
            $this->fail('Attempt to check existence for non-existent user did not generate HTTP 404');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(404, $exception->getStatusCode());
        }
    }
}


