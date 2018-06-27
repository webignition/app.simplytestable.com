<?php

namespace Tests\ApiBundle\Functional\Controller;

use SimplyTestable\ApiBundle\Controller\UserCreationController;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\UserPostActivationProperties;
use SimplyTestable\ApiBundle\Services\AccountPlanService;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use SimplyTestable\ApiBundle\Services\UserPostActivationPropertiesService;
use SimplyTestable\ApiBundle\Services\UserService;
use Tests\ApiBundle\Factory\StripeApiFixtureFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @group Controller/UserCreationController
 */
class UserCreationControllerTest extends AbstractControllerTest
{
    /**
     * @var UserCreationController
     */
    private $userCreationController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userCreationController = $this->container->get(UserCreationController::class);
    }

    public function testCreateActionPostRequest()
    {
        $router = $this->container->get('router');
        $requestUrl = $router->generate('usercreation_create');

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'POST',
            'parameters' => [
                'email' => 'foo-user@example.com',
                'password' => 'foo-password',
            ],
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    public function testCreateActionUserAlreadyActivated()
    {
        $userFactory = new UserFactory($this->container);
        $user = $userFactory->createAndActivateUser();

        $request = new Request([], [
            'email' => $user->getEmail(),
            'password' => 'not relevant',
        ]);

        /* @var RedirectResponse $response */
        $response = $this->userCreationController->createAction($request);

        $this->assertTrue($response instanceof RedirectResponse);

        $this->assertEquals(
            'http://localhost/user/user@example.com/',
            $response->getTargetUrl()
        );
    }

    public function testCreateActionExistingUserPasswordIsChanged()
    {
        $userService = $this->container->get(UserService::class);

        $userFactory = new UserFactory($this->container);
        $user = $userFactory->create();

        $initialPassword = $user->getPassword();

        $request = new Request([], [
            'email' => $user->getEmail(),
            'password' => 'different password',
        ]);

        /* @var RedirectResponse $response */
        $response = $this->userCreationController->createAction($request);

        $this->assertTrue($response->isSuccessful());

        $user = $userService->findUserByEmail($user->getEmail());

        $this->assertNotEquals($initialPassword, $user->getPassword());
    }

    /**
     * @dataProvider createActionDataProvider
     *
     * @param bool $createUser
     * @param string $email
     * @param string $password
     * @param string $coupon
     * @param string $plan
     * @param string $expectedPlanName
     * @param array $expectedPostActivationProperties
     */
    public function testCreateAction(
        $createUser,
        $email,
        $password,
        $coupon,
        $plan,
        $expectedPlanName,
        $expectedPostActivationProperties
    ) {
        $userAccountPlanService = $this->container->get(UserAccountPlanService::class);
        $userService = $this->container->get(UserService::class);
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $userPostActivationPropertiesRepository = $entityManager->getRepository(UserPostActivationProperties::class);

        if ($createUser) {
            $userFactory = new UserFactory($this->container);
            $userFactory->create();
        }

        $requestData = [
            'email' => $email,
            'password' => $password,
        ];

        if (!empty($coupon)) {
            $requestData['coupon'] = $coupon;
        }

        if (!empty($plan)) {
            $requestData['plan'] = $plan;
        }

        $request = new Request([], $requestData);

        /* @var RedirectResponse $response */
        $response = $this->userCreationController->createAction($request);

        $this->assertTrue($response->isSuccessful());

        $user = $userService->findUserByEmail(rawurldecode($email));

        $this->assertInstanceOf(User::class, $user);

        $userAccountPlan = $userAccountPlanService->getForUser($user);
        $postActivationProperties = $userPostActivationPropertiesRepository->findOneBy([
            'user' => $user,
        ]);

        if (is_null($expectedPlanName)) {
            $this->assertNull($userAccountPlan);
        } else {
            $this->assertEquals($expectedPlanName, $userAccountPlan->getPlan()->getName());
        }

        if (is_null($expectedPostActivationProperties)) {
            $this->assertNull($postActivationProperties);
        } else {
            $this->assertEquals(
                $expectedPostActivationProperties['planName'],
                $postActivationProperties->getAccountPlan()->getName()
            );

            $this->assertEquals(
                $expectedPostActivationProperties['coupon'],
                $postActivationProperties->getCoupon()
            );
        }
    }

    /**
     * @return array
     */
    public function createActionDataProvider()
    {
        return [
            'new user' => [
                'createUser' => false,
                'email' => 'user@example.com',
                'password' => 'user password',
                'coupon' => null,
                'plan' => null,
                'expectedPlanName' => 'basic',
                'expectedPostActivationProperties' => null,
            ],
            'new user; values encoded' => [
                'createUser' => false,
                'email' => rawurlencode('user@example.com'),
                'password' => rawurlencode('user password'),
                'coupon' => null,
                'plan' => null,
                'expectedPlanName' => 'basic',
                'expectedPostActivationProperties' => null,
            ],
            'existing user not enabled' => [
                'createUser' => true,
                'email' => 'user@example.com',
                'password' => 'user password',
                'coupon' => null,
                'plan' => null,
                'expectedPlanName' => 'basic',
                'expectedPostActivationProperties' => null,
            ],
            'plan not valid' => [
                'createUser' => true,
                'email' => 'user@example.com',
                'password' => 'user password',
                'coupon' => null,
                'plan' => 'foo',
                'expectedPlanName' => 'basic',
                'expectedPostActivationProperties' => null,
            ],
            'premium plan, no coupon' => [
                'createUser' => true,
                'email' => 'agency@example.com',
                'password' => 'user password',
                'coupon' => null,
                'plan' => 'agency',
                'expectedPlanName' => null,
                'expectedPostActivationProperties' => [
                    'planName' => 'agency',
                    'coupon' => null,
                ],
            ],
            'premium plan, has coupon' => [
                'createUser' => true,
                'email' => 'agency@example.com',
                'password' => 'user password',
                'coupon' => 'foo-coupon',
                'plan' => 'business',
                'expectedPlanName' => null,
                'expectedPostActivationProperties' => [
                    'planName' => 'business',
                    'coupon' => 'foo-coupon',
                ],
            ],
        ];
    }

    public function testActivateActionPostRequest()
    {
        $userService = $this->container->get(UserService::class);
        $publicUser = $userService->getPublicUser();

        $userFactory = new UserFactory($this->container);
        $user = $userFactory->create();

        $router = $this->container->get('router');
        $requestUrl = $router->generate('usercreation_activate', [
            'token' => $user->getConfirmationToken(),
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'POST',
            'user' => $publicUser,
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    public function testActivateActionInvalidToken()
    {
        $this->expectException(BadRequestHttpException::class);

        $this->userCreationController->activateAction('foo');
    }

    public function testActivateActionSuccessNoPostActivationProperties()
    {
        $userFactory = new UserFactory($this->container);
        $user = $userFactory->create();

        $response = $this->userCreationController->activateAction($user->getConfirmationToken());

        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($user->isEnabled());
    }

    public function testActivateActionSuccessHasPostActivationProperties()
    {
        $userPostActivationPropertiesService = $this->container->get(UserPostActivationPropertiesService::class);

        $userAccountPlanService = $this->container->get(UserAccountPlanService::class);
        $accountPlanService = $this->container->get(AccountPlanService::class);

        $agencyAccountPlan = $accountPlanService->get('agency');

        $userFactory = new UserFactory($this->container);
        $user = $userFactory->create();

        $postActivationProperties = $userPostActivationPropertiesService->create($user, $agencyAccountPlan, 'TMS');

        StripeApiFixtureFactory::set([
            StripeApiFixtureFactory::load('customer-hascard-hassub-hascoupon'),
        ]);

        $response = $this->userCreationController->activateAction($user->getConfirmationToken());

        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($user->isEnabled());

        $userAccountPlan = $userAccountPlanService->getForUser($user);

        $this->assertEquals(
            $postActivationProperties->getAccountPlan(),
            $userAccountPlan->getPlan()
        );

        $this->assertEmpty($postActivationProperties->getId());
    }
}
