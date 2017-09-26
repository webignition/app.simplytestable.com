<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class CancelUserEmailChangeTest extends BaseControllerJsonTestCase
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

        $this->getUserService()->setUser($user2);
        $this->getUserEmailChangeController('createAction')->createAction($user2->getEmail(), 'user1-new@example.com');

        $this->getUserService()->setUser($user1);

        try {
            $this->getUserEmailChangeController('cancelAction')->cancelAction($user2->getEmail());

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
        $this->getUserService()->setUser($user);

        $this->getUserEmailChangeController('createAction')->createAction($user->getEmail(), 'user1-new@example.com');
        $this->assertNotNull($this->getUserEmailChangeRequestService()->findByUser($user));

        $response = $this->getUserEmailChangeController('cancelAction')->cancelAction($user->getEmail());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($this->getUserEmailChangeRequestService()->findByUser($user));
    }

    public function testWhenNoEmailChangeRequestExists()
    {
        $email = 'user1@example.com';

        $user = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => $email,
        ]);
        $this->getUserService()->setUser($user);

        $response = $this->getUserEmailChangeController('cancelAction')->cancelAction($user->getEmail());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($this->getUserEmailChangeRequestService()->findByUser($user));
    }
}
