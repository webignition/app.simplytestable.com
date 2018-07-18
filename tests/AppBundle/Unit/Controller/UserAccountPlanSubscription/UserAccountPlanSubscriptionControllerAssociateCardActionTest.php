<?php

namespace Tests\AppBundle\Unit\Controller\UserAccountPlanSubscription;

use AppBundle\Entity\User;
use AppBundle\Entity\UserAccountPlan;
use AppBundle\Services\ApplicationStateService;
use AppBundle\Services\UserAccountPlanService;
use AppBundle\Services\UserService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Tests\AppBundle\Factory\MockFactory;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Tests\AppBundle\Factory\ModelFactory;

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
