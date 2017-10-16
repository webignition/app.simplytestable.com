<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\UserStripeEvent;

use SimplyTestable\ApiBundle\Controller\UserStripeEventController;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class ListActionTest extends BaseSimplyTestableTestCase
{
    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * @var UserStripeEventController
     */
    private $userStripeEventController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userFactory = new UserFactory($this->container);
        $this->userStripeEventController = new UserStripeEventController();
        $this->userStripeEventController->setContainer($this->container);
    }

    public function testWithPublicUser()
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $this->setUser($userService->getPublicUser());

        $response = $this->userStripeEventController->listAction('', '');
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testWithWrongUser()
    {
        $this->setUser($this->userFactory->create());

        $response = $this->userStripeEventController->listAction('user2@example.com', '');
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testWithNoStripeEvents()
    {
        $user = $this->userFactory->create();
        $this->setUser($user);

        $response = $this->userStripeEventController->listAction($user->getEmail(), '');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(array(), json_decode($response->getContent()));
    }

    public function testWithStripeEventsAndNoType()
    {
        $user = $this->userFactory->create();
        $this->setUser($user);

        $userAccountPlan = $this->getUserAccountPlanService()->subscribe(
            $user,
            $this->getAccountPlanService()->find('personal')
        );

        $this->loadStripeEventFixtures(__FUNCTION__, array(
            'customer' => $userAccountPlan->getStripeCustomer()
        ), $user);

        $response = $this->userStripeEventController->listAction($user->getEmail(), '');
        $this->assertEquals(200, $response->getStatusCode());

        $responseObject = json_decode($response->getContent());
        $this->assertInternalType('array', $responseObject);
        $this->assertEquals(14, count($responseObject));
    }

    public function testWithStripeEventsAndCustomerCreatedType()
    {
        $user = $this->userFactory->create();
        $this->setUser($user);

        $userAccountPlan = $this->getUserAccountPlanService()->subscribe(
            $user,
            $this->getAccountPlanService()->find('personal')
        );

        $this->loadStripeEventFixtures(__FUNCTION__, array(
            'customer' => $userAccountPlan->getStripeCustomer()
        ), $user);

        $responseObject = json_decode($this->userStripeEventController->listAction(
            $user->getEmail(),
            'customer.created'
        )->getContent());
        $this->assertEquals(1, count($responseObject));
    }

    public function testWithStripeEventsAndCustomerSubscriptionUpdatedType()
    {
        $user = $this->userFactory->create();
        $this->setUser($user);

        $userAccountPlan = $this->getUserAccountPlanService()->subscribe(
            $user,
            $this->getAccountPlanService()->find('personal')
        );

        $this->loadStripeEventFixtures(__FUNCTION__, array(
            'customer' => $userAccountPlan->getStripeCustomer()
        ), $user);

        $responseObject = json_decode($this->userStripeEventController->listAction(
            $user->getEmail(),
            'customer.subscription.updated'
        )->getContent());
        $this->assertEquals(3, count($responseObject));
    }

    private function loadStripeEventFixtures($method, $ammendments, $user)
    {
        $fixturePath = $this->getFixturesDataPath($method) . '/StripeEvents';

        $directoryIterator = new \DirectoryIterator($fixturePath);
        $fixturePathNames = array();

        foreach ($directoryIterator as $directoryItem) {
            if ($directoryItem->isFile() && $directoryItem->getFileInfo()->getExtension() == 'json') {
                $fixturePathNames[] = $directoryItem->getPathname();
            }
        }

        sort($fixturePathNames);

        foreach ($fixturePathNames as $fixturePathName) {
            $fixtureObject = json_decode(file_get_contents($fixturePathName));

            if (is_array($ammendments)) {
                if (isset($ammendments['customer'])) {
                    if ($fixtureObject->data->object->object == 'customer') {
                        $fixtureObject->data->object->id = $ammendments['customer'];
                    } elseif (isset($fixtureObject->data->object->customer)) {
                        $fixtureObject->data->object->customer = $ammendments['customer'];
                    }
                }
            }

            $this->getStripeEventService()->create(
                $fixtureObject->id,
                $fixtureObject->type,
                $fixtureObject->livemode,
                json_encode($fixtureObject->data->object),
                $user
            );
        }
    }
}
