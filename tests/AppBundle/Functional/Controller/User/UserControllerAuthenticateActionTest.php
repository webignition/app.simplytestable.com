<?php

namespace Tests\AppBundle\Functional\Controller\User;

use AppBundle\Entity\User;
use AppBundle\Services\UserService;
use Tests\AppBundle\Factory\UserFactory;
use Symfony\Component\Security\Core\Exception\DisabledException;

/**
 * @group Controller/UserController
 */
class UserControllerAuthenticateActionTest extends AbstractUserControllerTest
{
    public function testAuthenticateActionGetRequest()
    {
        $userService = self::$container->get(UserService::class);
        $user = $userService->getPublicUser();

        $this->createCrawler($user);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    public function testAuthenticateActionGetRequestDisabledUser()
    {
        $this->expectException(DisabledException::class);

        $userFactory = new UserFactory(self::$container);
        $user = $userFactory->create();

        $this->createCrawler($user);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    /**
     * @param User $user
     *
     * @return string
     */
    private function createRequestUrl(User $user)
    {
        $router = self::$container->get('router');

        return $router->generate('user_authenticate', [
            'email_canonical' => $user->getEmail(),
        ]);
    }

    /**
     * @param User $user
     */
    private function createCrawler(User $user)
    {
        $this->getCrawler([
            'url' => $this->createRequestUrl($user),
            'method' => 'GET',
            'user' => $user,
        ]);
    }
}
