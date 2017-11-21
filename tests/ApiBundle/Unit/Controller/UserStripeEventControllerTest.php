<?php

namespace Tests\ApiBundle\Unit\Controller;

use SimplyTestable\ApiBundle\Controller\UserStripeEventController;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\StripeEventService;
use SimplyTestable\ApiBundle\Services\UserService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Tests\ApiBundle\Factory\MockFactory;
use Tests\ApiBundle\Factory\ModelFactory;

/**
 * @group Controller/UserStripeEventController
 */
class UserStripeEventControllerTest extends \PHPUnit_Framework_TestCase
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
