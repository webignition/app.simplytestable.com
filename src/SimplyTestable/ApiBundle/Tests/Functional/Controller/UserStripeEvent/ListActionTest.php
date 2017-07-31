<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\UserStripeEvent;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;

class ListActionTest extends BaseControllerJsonTestCase
{
    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userFactory = new UserFactory($this->container);
    }

    public function testWithPublicUser()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $response = $this->getUserStripeEventController('listAction')->listAction('', '');
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testWithWrongUser()
    {
        $this->getUserService()->setUser($this->userFactory->create());

        $response = $this->getUserStripeEventController('listAction')->listAction('user2@example.com', '');
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testWithNoStripeEvents()
    {
        $user = $this->userFactory->create();
        $this->getUserService()->setUser($user);

        $response = $this->getUserStripeEventController('listAction')->listAction($user->getEmail(), '');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(array(), json_decode($response->getContent()));
    }

    public function testWithStripeEventsAndNoType()
    {
        $user = $this->userFactory->create();
        $this->getUserService()->setUser($user);

        $userAccountPlan = $this->getUserAccountPlanService()->subscribe(
            $user,
            $this->getAccountPlanService()->find('personal')
        );

        $this->loadStripeEventFixtures(__FUNCTION__, array(
            'customer' => $userAccountPlan->getStripeCustomer()
        ), $user);

        $response = $this->getUserStripeEventController('listAction')->listAction($user->getEmail(), '');
        $this->assertEquals(200, $response->getStatusCode());

        $responseObject = json_decode($response->getContent());
        $this->assertInternalType('array', $responseObject);
        $this->assertEquals(14, count($responseObject));
    }

    public function testWithStripeEventsAndCustomerCreatedType()
    {
        $user = $this->userFactory->create();
        $this->getUserService()->setUser($user);

        $userAccountPlan = $this->getUserAccountPlanService()->subscribe(
            $user,
            $this->getAccountPlanService()->find('personal')
        );

        $this->loadStripeEventFixtures(__FUNCTION__, array(
            'customer' => $userAccountPlan->getStripeCustomer()
        ), $user);

        $responseObject = json_decode($this->getUserStripeEventController('listAction')->listAction(
            $user->getEmail(),
            'customer.created'
        )->getContent());
        $this->assertEquals(1, count($responseObject));
    }

    public function testWithStripeEventsAndCustomerSubscriptionUpdatedType()
    {
        $user = $this->userFactory->create();
        $this->getUserService()->setUser($user);

        $userAccountPlan = $this->getUserAccountPlanService()->subscribe(
            $user,
            $this->getAccountPlanService()->find('personal')
        );

        $this->loadStripeEventFixtures(__FUNCTION__, array(
            'customer' => $userAccountPlan->getStripeCustomer()
        ), $user);

        $responseObject = json_decode($this->getUserStripeEventController('listAction')->listAction(
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
