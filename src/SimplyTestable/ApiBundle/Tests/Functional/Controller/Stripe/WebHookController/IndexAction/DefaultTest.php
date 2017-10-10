<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Stripe\WebHookController\IndexAction;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class DefaultTest extends IndexActionTest
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

    public function testWithStripeInvoicePaymentFailedEventForUnknownUser()
    {
        $fixture = $this->getFixture(
            $this->getFixturesDataPath(__FUNCTION__). '/StripeEvents/invoice.payment_failed.event.json'
        );
        $response = $this->getStripeWebHookController('indexAction', array(
            'event' => $fixture
        ))->indexAction();

        $this->assertEquals(200, $response->getStatusCode());

        $responseObject = json_decode($response->getContent());
        $stripeEvent = $this->getStripeEventService()->getByStripeId($responseObject->stripe_id);

        $this->assertEquals($responseObject->stripe_id, $stripeEvent->getStripeId());
    }

    public function testWithStripeInvoicePaymentFailedEventForKnownUser()
    {
        $user = $this->userFactory->create();
        $this->setUser($user);

        $userAccountPlan = $this->getUserAccountPlanService()->subscribe(
            $user,
            $this->getAccountPlanService()->find('personal')
        );

        $fixture = $this->getFixture(
            $this->getFixturesDataPath(__FUNCTION__). '/StripeEvents/invoice.payment_failed.event.json'
        );
        $fixtureObject = json_decode($fixture);

        $fixtureObject->data->object->customer = $userAccountPlan->getStripeCustomer();

        $response = $this->getStripeWebHookController('indexAction', array(
            'event' => json_encode($fixtureObject)
        ))->indexAction();

        $this->assertEquals(200, $response->getStatusCode());

        $responseObject = json_decode($response->getContent());
        $this->assertEquals($user->getEmail(), $responseObject->user);

        $stripeEvent = $this->getStripeEventService()->getByStripeId($responseObject->stripe_id);
        $this->assertEquals($user->getId(), $stripeEvent->getUser()->getId());
    }

    public function testWithStripeCustomerSubscriptionCreatedEventForUnknownUser()
    {
        $fixture = $this->getFixture(
            $this->getFixturesDataPath(__FUNCTION__). '/StripeEvents/customer.subscription.created.event.json'
        );
        $response = $this->getStripeWebHookController('indexAction', array(
            'event' => $fixture
        ))->indexAction();

        $this->assertEquals(200, $response->getStatusCode());

        $responseObject = json_decode($response->getContent());
        $stripeEvent = $this->getStripeEventService()->getByStripeId($responseObject->stripe_id);

        $this->assertEquals($responseObject->stripe_id, $stripeEvent->getStripeId());
    }

    public function testWithStripeCustomerSubscriptionCreatedEventForKnownUser()
    {
        $user = $this->userFactory->create();
        $this->setUser($user);

        $userAccountPlan = $this->getUserAccountPlanService()->subscribe(
            $user,
            $this->getAccountPlanService()->find('personal')
        );

        $fixture = $this->getFixture(
            $this->getFixturesDataPath(__FUNCTION__). '/StripeEvents/customer.subscription.created.event.json'
        );
        $fixtureObject = json_decode($fixture);

        $fixtureObject->data->object->customer = $userAccountPlan->getStripeCustomer();

        $response = $this->getStripeWebHookController('indexAction', array(
            'event' => json_encode($fixtureObject)
        ))->indexAction();

        $this->assertEquals(200, $response->getStatusCode());

        $responseObject = json_decode($response->getContent());
        $this->assertEquals($user->getEmail(), $responseObject->user);

        $stripeEvent = $this->getStripeEventService()->getByStripeId($responseObject->stripe_id);
        $this->assertEquals($user->getId(), $stripeEvent->getUser()->getId());
    }


    public function testWithStripeInvoiceCreatedEventForUnknownUser()
    {
        $fixture = $this->getFixture(
            $this->getFixturesDataPath(__FUNCTION__). '/StripeEvents/invoice.created.event.json'
        );
        $response = $this->getStripeWebHookController('indexAction', array(
            'event' => $fixture
        ))->indexAction();

        $this->assertEquals(200, $response->getStatusCode());

        $responseObject = json_decode($response->getContent());
        $stripeEvent = $this->getStripeEventService()->getByStripeId($responseObject->stripe_id);

        $this->assertEquals($responseObject->stripe_id, $stripeEvent->getStripeId());
    }

    public function testWithStripeInvoiceCreatedEventForKnownUser()
    {
        $user = $this->userFactory->create();
        $this->setUser($user);

        $userAccountPlan = $this->getUserAccountPlanService()->subscribe(
            $user,
            $this->getAccountPlanService()->find('personal')
        );

        $fixture = $this->getFixture(
            $this->getFixturesDataPath(__FUNCTION__). '/StripeEvents/invoice.created.event.json'
        );
        $fixtureObject = json_decode($fixture);

        $fixtureObject->data->object->customer = $userAccountPlan->getStripeCustomer();

        $response = $this->getStripeWebHookController('indexAction', array(
            'event' => json_encode($fixtureObject)
        ))->indexAction();

        $this->assertEquals(200, $response->getStatusCode());

        $responseObject = json_decode($response->getContent());
        $this->assertEquals($user->getEmail(), $responseObject->user);

        $stripeEvent = $this->getStripeEventService()->getByStripeId($responseObject->stripe_id);
        $this->assertEquals($user->getId(), $stripeEvent->getUser()->getId());
    }

    public function testWithStripeCustomerUpdatedEventForUnknownUser()
    {
        $fixture = $this->getFixture(
            $this->getFixturesDataPath(__FUNCTION__). '/StripeEvents/customer.updated.event.json'
        );
        $response = $this->getStripeWebHookController('indexAction', array(
            'event' => $fixture
        ))->indexAction();

        $this->assertEquals(200, $response->getStatusCode());

        $responseObject = json_decode($response->getContent());
        $stripeEvent = $this->getStripeEventService()->getByStripeId($responseObject->stripe_id);

        $this->assertEquals($responseObject->stripe_id, $stripeEvent->getStripeId());
    }

    public function testWithStripeCustomerUpdatedEventForKnownUser()
    {
        $user = $this->userFactory->create();
        $this->setUser($user);

        $userAccountPlan = $this->getUserAccountPlanService()->subscribe(
            $user,
            $this->getAccountPlanService()->find('personal')
        );

        $fixture = $this->getFixture(
            $this->getFixturesDataPath(__FUNCTION__). '/StripeEvents/customer.updated.event.json'
        );
        $fixtureObject = json_decode($fixture);

        $fixtureObject->data->object->id = $userAccountPlan->getStripeCustomer();

        $response = $this->getStripeWebHookController('indexAction', array(
            'event' => json_encode($fixtureObject)
        ))->indexAction();

        $this->assertEquals(200, $response->getStatusCode());

        $responseObject = json_decode($response->getContent());
        $this->assertEquals($user->getEmail(), $responseObject->user);

        $stripeEvent = $this->getStripeEventService()->getByStripeId($responseObject->stripe_id);
        $this->assertEquals($user->getId(), $stripeEvent->getUser()->getId());
    }

    public function testStripeEventDataIsPersisted()
    {
        $user = $this->userFactory->create();
        $this->setUser($user);

        $userAccountPlan = $this->getUserAccountPlanService()->subscribe(
            $user,
            $this->getAccountPlanService()->find('personal')
        );

        $fixture = $this->getFixture(
            $this->getFixturesDataPath(__FUNCTION__). '/StripeEvents/customer.subscription.created.event.json'
        );
        $fixtureObject = json_decode($fixture);

        $fixtureObject->data->object->customer = $userAccountPlan->getStripeCustomer();

        $response = $this->getStripeWebHookController('indexAction', array(
            'event' => json_encode($fixtureObject)
        ))->indexAction();

        $this->assertEquals(200, $response->getStatusCode());

        $responseObject = json_decode($response->getContent());
        $this->assertEquals($user->getEmail(), $responseObject->user);

        $stripeEvent = $this->getStripeEventService()->getByStripeId($responseObject->stripe_id);
        $this->assertEquals($user->getId(), $stripeEvent->getUser()->getId());
        $this->assertNotNull($stripeEvent->getStripeEventData());

        $this->assertEquals(json_encode($fixtureObject), $stripeEvent->getStripeEventData());
        $this->assertEquals(
            new \webignition\Model\Stripe\Event\Event(json_encode($fixtureObject)),
            $stripeEvent->getStripeEventObject()
        );
    }

    public function testValidEventCreatesProcessEventResqueJob()
    {
        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');

        $fixture = $this->getFixture(
            $this->getFixturesDataPath(__FUNCTION__). '/StripeEvents/invoice.payment_failed.event.json'
        );

        $response = $this->getStripeWebHookController('indexAction', array(
            'event' => $fixture
        ))->indexAction();

        $responseObject = json_decode($response->getContent());

        $stripeEvent = $this->getStripeEventService()->getByStripeId($responseObject->stripe_id);

        $this->assertTrue($resqueQueueService->contains(
            'stripe-event',
            array(
                'stripeId' => $stripeEvent->getStripeId()
            )
        ));
    }
}
