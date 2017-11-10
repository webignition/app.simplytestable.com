<?php

namespace Tests\ApiBundle\Functional\Controller\User;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\UserService;
use Tests\ApiBundle\Factory\UserFactory;
use Symfony\Component\Security\Core\Exception\DisabledException;

class UserControllerAuthenticateActionTest extends AbstractUserControllerTest
{
    public function testAuthenticateActionGetRequest()
    {
        $userService = $this->container->get(UserService::class);
        $user = $userService->getPublicUser();

        $this->createCrawler($user);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    public function testAuthenticateActionGetRequestDisabledUser()
    {
        $this->expectException(DisabledException::class);

        $userFactory = new UserFactory($this->container);
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
        $router = $this->container->get('router');

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
