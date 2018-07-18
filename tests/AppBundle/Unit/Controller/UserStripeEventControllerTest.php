<?php

namespace Tests\AppBundle\Unit\Controller;

use AppBundle\Controller\UserStripeEventController;
use AppBundle\Entity\User;
use AppBundle\Services\StripeEventService;
use AppBundle\Services\UserService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Tests\AppBundle\Factory\MockFactory;
use Tests\AppBundle\Factory\ModelFactory;

/**
 * @group Controller/UserStripeEventController
 */
class UserStripeEventControllerTest extends \PHPUnit\Framework\TestCase
{
    public function testListActionPublicUser()
    {
        $user = new User();

        $userStripeEventController = $this->createUserStripeEventController([
            UserService::class => MockFactory::createUserService([
                'isPublicUser' => [
                    'with' => $user,
                    'return' => true,
                ],
            ]),
        ]);

        $this->expectException(BadRequestHttpException::class);

        $userStripeEventController->listAction($user, $user->getEmail(), 'foo');
    }

    public function testListActionInvalidUser()
    {
        $user = ModelFactory::createUser([
            ModelFactory::USER_EMAIL => 'user@example.com',
        ]);

        $userStripeEventController = $this->createUserStripeEventController([
            UserService::class => MockFactory::createUserService([
                'isPublicUser' => [
                    'with' => $user,
                    'return' => false,
                ],
            ]),
        ]);

        $this->expectException(BadRequestHttpException::class);

        $userStripeEventController->listAction($user, 'foo@example.com', 'foo');
    }

    /**
     * @param array $services
     *
     * @return UserStripeEventController
     */
    private function createUserStripeEventController($services = [])
    {
        if (!isset($services[UserService::class])) {
            $services[UserService::class] = MockFactory::createUserService();
        }

        if (!isset($services[StripeEventService::class])) {
            $services[StripeEventService::class] = MockFactory::createStripeEventService();
        }

        $userStripeEventController = new UserStripeEventController(
            $services[UserService::class],
            $services[StripeEventService::class]
        );

        return $userStripeEventController;
    }
}
