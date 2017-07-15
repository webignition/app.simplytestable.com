<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Stripe\Event\ProcessCommand;

abstract class NonErrorCasesTest extends ProcessCommandTest {

    protected $stripeId;
    protected $stripeEvent;

    abstract protected function getExpectedNotificationBodyEventName();
    abstract protected function getStripeEventFixturePaths();

    public function setUp() {
        parent::setUp();

        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureSet()));

        $user = $this->getTestUser();
        $this->getUserService()->setUser($user);

        $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction(
            $user->getEmail(),
            'agency'
        );

        foreach ($this->getStripeEventFixturePaths() as $fixturePath) {
            $response = $this->getStripeWebHookController('indexAction', array(
                'event' => $this->getFixtureContent($fixturePath)
            ))->indexAction();
        }

        $responseObject = json_decode($response->getContent());
        $this->stripeId = $responseObject->stripe_id;
    }

    protected function getHttpFixtureSet() {
        return array(
            "HTTP/1.1 200 OK"
        );
    }

    public function testEventIsProcessed() {
        $this->assertTrue($this->stripeEvent->getIsProcessed());
    }


    public function testNotificationBodyFields() {
        $expectedNotificationBodyFields = $this->getExpectedNotificationBodyFields();

        if (key($expectedNotificationBodyFields) === 0) {
            foreach ($expectedNotificationBodyFields as $index => $fieldSet) {
                foreach ($fieldSet as $name => $expectedValue) {
                    $this->assertNotificationBodyField($name, $expectedValue, $index);
                }
            }
        } else {
            foreach ($expectedNotificationBodyFields as $name => $expectedValue) {
                $this->assertNotificationBodyField($name, $expectedValue);
            }
        }
    }

    protected function getExpectedNotificationBodyFields() {
        return array(
            'event' => $this->getExpectedNotificationBodyEventName(),
            'user' => 'user@example.com'
        );
    }

    protected function getFixtureReplacements() {
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($this->getUserService()->getUser());

        return array(
            '{{stripe_customer}}' => $userAccountPlan->getStripeCustomer()
        );
    }


    protected function assertNotificationBodyField($name, $expectedValue, $index = 0) {
        /* @var $postFields \Guzzle\Http\QueryString */
        $requests = $this->getHttpClientService()->getHistoryPlugin()->getAll();
        $postFields = $requests[$index]['request']->getPostFields();

        $this->assertTrue($postFields->hasKey($name), 'Notification body field "'.$name.'" not set');
        $this->assertEquals($expectedValue, $postFields->get($name));
    }


    protected function getFixtureContent($path) {
        $fixtureReplacements = $this->getFixtureReplacements();

        return str_replace(
                array_keys($fixtureReplacements),
                array_values($fixtureReplacements),
                $this->getFixture($path)
        );
    }

}