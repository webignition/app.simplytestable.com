<?php

namespace App\Tests\Unit\Controller\UserAccountPlanSubscription;

use App\Entity\User;
use App\Entity\UserAccountPlan;
use App\Services\ApplicationStateService;
use App\Services\UserAccountPlanService;
use App\Services\UserService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\Tests\Factory\MockFactory;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use App\Tests\Factory\ModelFactory;

/**
 * @group Controller/UserAccountPlanSubscriptionController
 */
class UserAccountPlanSubscriptionControllerAssociateCardActionTest extends
 AbstractUserAccountPlanSubscriptionControllerTest
{
    public function testAssociateCardActionInMaintenanceReadOnlyMode()
    {
        $userAccountPlanSubscriptionController = $this->createUserAccountPlanSubscriptionController([
            ApplicationStateService::class => MockFactory::createApplicationStateService(true),
        ]);

        $this->expectException(ServiceUnavailableHttpException::class);

        $userAccountPlanSubscriptionController->associateCardAction(
            new User(),
            'user@example.com',
            'token'
        );
    }

    public function testAssociateCardActionPublicUser()
    {
        $user = new User();

        $userAccountPlanSubscriptionController = $this->createUserAccountPlanSubscriptionController([
            UserService::class => MockFactory::createUserService([
                'isPublicUser' => [
                    'with' => $user,
                    'return' => true,
                ],
            ]),
        ]);

        $this->expectException(BadRequestHttpException::class);

        $userAccountPlanSubscriptionController->associateCardAction(
            $user,
            'user@example.com',
            'token'
        );
    }

    public function testAssociateCardActionInvalidUser()
    {
        $user = ModelFactory::createUser([
            ModelFactory::USER_EMAIL => 'user@example.com',
        ]);

        $userAccountPlanSubscriptionController = $this->createUserAccountPlanSubscriptionController([
            UserService::class => MockFactory::createUserService([
                'isPublicUser' => [
                    'with' => $user,
                    'return' => false,
                ],
            ]),
        ]);

        $this->expectException(BadRequestHttpException::class);

        $userAccountPlanSubscriptionController->associateCardAction(
            $user,
            'foo@example.com',
            'token'
        );
    }

    public function testAssociateCardActionInvalidToken()
    {
        $user = ModelFactory::createUser([
            ModelFactory::USER_EMAIL => 'user@example.com',
        ]);

        $userAccountPlanSubscriptionController = $this->createUserAccountPlanSubscriptionController([
            UserService::class => MockFactory::createUserService([
                'isPublicUser' => [
                    'with' => $user,
                    'return' => false,
                ],
            ]),
        ]);

        $this->expectException(BadRequestHttpException::class);

        $userAccountPlanSubscriptionController->associateCardAction(
            $user,
            $user->getEmail(),
            'token'
        );
    }

    public function testAssociateCardActionUserHasNoStripeCustomer()
    {
        $user = ModelFactory::createUser([
            ModelFactory::USER_EMAIL => 'user@example.com',
        ]);

        $userAccountPlan = new UserAccountPlan();

        $userAccountPlanSubscriptionController = $this->createUserAccountPlanSubscriptionController([
            UserService::class => MockFactory::createUserService([
                'isPublicUser' => [
                    'with' => $user,
                    'return' => false,
                ],
            ]),
            UserAccountPlanService::class => MockFactory::createUserAccountPlanService([
                'getForUser' => [
                    'with' => $user,
                    'return' => $userAccountPlan,
                ],
            ]),
        ]);

        $this->expectException(BadRequestHttpException::class);

        $userAccountPlanSubscriptionController->associateCardAction(
            $user,
            $user->getEmail(),
            'tok_01234567891234'
        );
    }
}
