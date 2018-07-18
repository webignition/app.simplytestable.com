<?php

namespace Tests\AppBundle\Functional\Controller\UserEmailChange;

use AppBundle\Entity\UserEmailChangeRequest;
use Tests\AppBundle\Factory\UserFactory;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * @group Controller/UserEmailChangeController
 */
class UserEmailChangeControllerCreateActionTest extends AbstractUserEmailChangeControllerTest
{
    public function testCreateActionPostRequest()
    {
        $router = self::$container->get('router');
        $requestUrl = $router->generate('user_email_change_request_create', [
            'email_canonical' =>  $this->user->getEmail(),
            'new_email' => 'foo@example.com',
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'POST',
            'user' => $this->user,
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    public function testCreateActionWithExistingMatchingRequest()
    {
        $newEmail = 'new-email@example.com';

        $this->createEmailChangeRequest($this->user, $newEmail);

        $response = $this->userEmailChangeController->createAction(
            $this->user,
            $this->user->getEmail(),
            $newEmail
        );

        $this->assertTrue($response->isSuccessful());
    }

    public function testCreateActionWithExistingNonMatchingRequest()
    {
        $this->expectException(ConflictHttpException::class);

        $newEmail = 'new-email@example.com';

        $this->createEmailChangeRequest($this->user, 'foo@example.com');

        $this->userEmailChangeController->createAction(
            $this->user,
            $this->user->getEmail(),
            $newEmail
        );
    }

    public function testCreateActionInvalidNewEmail()
    {
        $this->expectException(BadRequestHttpException::class);

        $newEmail = 'foo';

        $this->userEmailChangeController->createAction(
            $this->user,
            $this->user->getEmail(),
            $newEmail
        );
    }

    public function testCreateActionEmailTakenByUser()
    {
        $this->expectException(ConflictHttpException::class);

        $newEmail = 'new-email@example.com';

        $this->userFactory->create([
            UserFactory::KEY_EMAIL => $newEmail,
        ]);

        $this->userEmailChangeController->createAction(
            $this->user,
            $this->user->getEmail(),
            $newEmail
        );
    }

    public function testCreateActionNewEmailTakenByEmailChangeRequest()
    {
        $this->expectException(ConflictHttpException::class);

        $newEmail = 'new-email@example.com';

        $differentUser = $this->userFactory->create([
            UserFactory::KEY_EMAIL => 'differentuser@example.com',
        ]);

        $this->createEmailChangeRequest($differentUser, $newEmail);

        $this->userEmailChangeController->createAction(
            $this->user,
            $this->user->getEmail(),
            $newEmail
        );
    }

    public function testCreateActionSuccess()
    {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $emailChangeRequestRepository = $entityManager->getRepository(UserEmailChangeRequest::class);

        $newEmail = 'new-email@example.com';

        $response = $this->userEmailChangeController->createAction(
            $this->user,
            $this->user->getEmail(),
            $newEmail
        );

        $this->assertTrue($response->isSuccessful());

        $emailChangeRequest = $emailChangeRequestRepository->findOneBy([
            'user' => $this->user,
        ]);

        $this->assertInstanceOf(UserEmailChangeRequest::class, $emailChangeRequest);
    }
}
