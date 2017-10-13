<?php

namespace SimplyTestable\ApiBundle\Tests\Command;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\UserService;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseTestCase;

class UserServiceTest extends BaseTestCase
{
    /**
     * @var UserService
     */
    private $userService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userService = $this->container->get('simplytestable.services.userservice');
    }

    public function testGetPublicUser()
    {
        $publicUser = $this->userService->getPublicUser();

        $this->assertInstanceOf(User::class, $publicUser);
        $this->assertEquals('public@simplytestable.com', $publicUser->getEmail());
    }

    public function testGetAdminUser()
    {
        $adminUserEmail = $this->container->getParameter('admin_user_email');
        $adminUser = $this->userService->getAdminUser();

        $this->assertInstanceOf(User::class, $adminUser);
        $this->assertEquals($adminUserEmail, $adminUser->getEmail());
    }

    /**
     * @dataProvider isPublicUserDataProvider
     *
     * @param string $userEmail
     * @param bool $expectedIsPublicUser
     */
    public function testIsPublicUser($userEmail, $expectedIsPublicUser)
    {
        $userFactory = new UserFactory($this->container);
        $user = $userFactory->create([
            UserFactory::KEY_EMAIL => $userEmail,
        ]);

        $this->assertEquals($expectedIsPublicUser, $this->userService->isPublicUser($user));
    }

    /**
     * @return array
     */
    public function isPublicUserDataProvider()
    {
        return [
            'public user' => [
                'userEmail' => 'public@simplytestable.com',
                'expectedIsPublicUser' => true,
            ],
            'private user' => [
                'userEmail' => 'foo@simplytestable.com',
                'expectedIsPublicUser' => false,
            ],
        ];
    }
}
