<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\User;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserControllerHasInvitesActionTest extends AbstractUserControllerTest
{
    public function testHasInvitesActionGetRequest()
    {
        $teamInviteService = $this->container->get('simplytestable.services.teaminviteservice');
        $userService = $this->container->get('simplytestable.services.userservice');

        $userFactory = new UserFactory($this->container);
        $users = $userFactory->createPublicPrivateAndTeamUserSet();

        $teamInviteService->get($users['leader'], $users['private']);

        $router = $this->container->get('router');
        $requestUrl = $router->generate('user_hasinvites', [
            'email_canonical' => $users['private']->getEmail(),
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'GET',
            'user' => $userService->getAdminUser(),
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    public function testHasInvitesActionUserNotFound()
    {
        $this->setExpectedException(NotFoundHttpException::class);

        $this->userController->hasInvitesAction('foo@example.com');
    }

    public function testHasInvitesActionNoInvites()
    {
        $this->setExpectedException(NotFoundHttpException::class);

        $userService = $this->container->get('simplytestable.services.userservice');
        $publicUser = $userService->getPublicUser();

        $this->userController->hasInvitesAction($publicUser->getEmail());
    }

    public function testHasInvitesActionSuccess()
    {
        $teamInviteService = $this->container->get('simplytestable.services.teaminviteservice');

        $userFactory = new UserFactory($this->container);
        $users = $userFactory->createPublicPrivateAndTeamUserSet();

        $teamInviteService->get($users['leader'], $users['private']);

        $response = $this->userController->hasInvitesAction($users['private']->getEmail());

        $this->assertTrue($response->isSuccessful());
    }
}
