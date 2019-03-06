<?php

namespace App\Tests\Command;

use App\Entity\User;
use App\Services\UserService;
use App\Tests\Services\UserFactory;
use App\Tests\Functional\AbstractBaseTestCase;

class UserServiceTest extends AbstractBaseTestCase
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

        $this->userService = self::$container->get(UserService::class);
    }

    public function testGetPublicUser()
    {
        $publicUser = $this->userService->getPublicUser();

        $this->assertInstanceOf(User::class, $publicUser);
        $this->assertEquals('public@simplytestable.com', $publicUser->getEmail());
    }

    public function testGetAdminUser()
    {
        $adminUserEmail = getenv('ADMIN_USER_EMAIL');
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
        $userFactory = self::$container->get(UserFactory::class);
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

    /**
     * @dataProvider isSpecialUserDataProvider
     *
     * @param string $userEmail
     * @param bool $expectedIsSpecialUser
     */
    public function testIsSpecialUser($userEmail, $expectedIsSpecialUser)
    {
        $userFactory = self::$container->get(UserFactory::class);
        $user = $userFactory->create([
            UserFactory::KEY_EMAIL => $userEmail,
        ]);

        $this->assertEquals($expectedIsSpecialUser, $this->userService->isSpecialUser($user));
    }

    /**
     * @return array
     */
    public function isSpecialUserDataProvider()
    {
        return [
            'public user' => [
                'userEmail' => 'public@simplytestable.com',
                'expectedIsSpecialUser' => true,
            ],
            'admin user' => [
                'userEmail' => 'admin@simplytestable.com',
                'expectedIsSpecialUser' => true,
            ],
            'private user' => [
                'userEmail' => 'foo@simplytestable.com',
                'expectedIsSpecialUser' => false,
            ],
        ];
    }

    public function testCreateUser()
    {
        $user = $this->userService->create('foo@example.com', 'password');

        $this->assertInstanceOf(User::class, $user);
    }

    /**
     * @dataProvider existsDataProvider
     *
     * @param string $userEmail
     * @param bool $expectedExists
     */
    public function testExists($userEmail, $expectedExists)
    {
        $this->assertEquals(
            $expectedExists,
            $this->userService->exists($userEmail)
        );
    }

    /**
     * @return array
     */
    public function existsDataProvider()
    {
        return [
            'public user' => [
                'userEmail' => 'public@simplytestable.com',
                'expectedExists' => true,
            ],
            'admin user' => [
                'userEmail' => 'admin@simplytestable.com',
                'expectedExists' => true,
            ],
            'new user' => [
                'userEmail' => 'foo@simplytestable.com',
                'expectedExists' => false,
            ],
        ];
    }

    public function testGetConfirmationToken()
    {
        $userFactory = self::$container->get(UserFactory::class);

        $user = $userFactory->create();
        $this->assertNotEmpty($user->getConfirmationToken());

        $this->assertRegExp(
            '/[A-Za-z0-9\-_]{43}/',
            $this->userService->getConfirmationToken($user)
        );

        $user->setConfirmationToken(null);
        $this->assertEmpty($user->getConfirmationToken());

        $this->assertRegExp(
            '/[A-Za-z0-9\-_]{43}/',
            $this->userService->getConfirmationToken($user)
        );
    }
}
