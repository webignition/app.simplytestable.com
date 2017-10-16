<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller;

use SimplyTestable\ApiBundle\Controller\UserEmailChangeController;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class CancelUserEmailChangeTest extends BaseSimplyTestableTestCase
{
    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * @var UserEmailChangeController
     */
    private $userEmailChangeController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userFactory = new UserFactory($this->container);
        $this->userEmailChangeController = new UserEmailChangeController();
        $this->userEmailChangeController->setContainer($this->container);
    }

    public function testForDifferentUser()
    {
        $email1 = 'user1@example.com';
        $user1 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => $email1,
        ]);

        $email2 = 'user2@example.com';
        $user2 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => $email2,
        ]);

        $this->setUser($user2);
        $this->userEmailChangeController->createAction($user2->getEmail(), 'user1-new@example.com');

        $this->setUser($user1);

        try {
            $this->userEmailChangeController->cancelAction($user2->getEmail());

            $this->fail('Attempt to cancel for different user did not generate HTTP 404');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(404, $exception->getStatusCode());
        }
    }

    public function testForCorrectUser()
    {
        $email = 'user1@example.com';

        $user = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => $email,
        ]);
        $this->setUser($user);

        $this->userEmailChangeController->createAction($user->getEmail(), 'user1-new@example.com');
        $this->assertNotNull($this->getUserEmailChangeRequestService()->findByUser($user));

        $response = $this->userEmailChangeController->cancelAction($user->getEmail());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($this->getUserEmailChangeRequestService()->findByUser($user));
    }

    public function testWhenNoEmailChangeRequestExists()
    {
        $email = 'user1@example.com';

        $user = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => $email,
        ]);
        $this->setUser($user);

        $response = $this->userEmailChangeController->cancelAction($user->getEmail());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($this->getUserEmailChangeRequestService()->findByUser($user));
    }
}
