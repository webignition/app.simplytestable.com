<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\EventListener\Stripe;

use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use SimplyTestable\ApiBundle\Services\TestHttpClientService;
use SimplyTestable\ApiBundle\Tests\Factory\StripeEventFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

abstract class AbstractStripeEventListenerTest extends BaseSimplyTestableTestCase
{
    public function createStripeEvents($stripeEventFixtures, $user)
    {
        $stripeEventService = $this->container->get('simplytestable.services.stripeeventservice');

        $stripeEvents = [];

        foreach ($stripeEventFixtures as $fixtureName => $modifiers) {
            $stripeEventFixture = StripeEventFixtureFactory::load($fixtureName, $modifiers);

            $stripeEvents[] = $stripeEventService->create(
                $stripeEventFixture['id'],
                $stripeEventFixture['type'],
                $stripeEventFixture['livemode'],
                json_encode($stripeEventFixture),
                $user
            );
        }

        return array_pop($stripeEvents);
    }

    /**
     * @param TestHttpClientService $httpClientService
     * @param array $expectedWebClientRequestDataCollection
     */
    protected function assertWebClientRequests(
        TestHttpClientService $httpClientService,
        $expectedWebClientRequestDataCollection
    ) {
        $httpTransactions = $httpClientService->getHistoryPlugin()->getAll();

        if (empty($expectedWebClientRequestDataCollection)) {
            $this->assertEmpty($httpTransactions);
        } else {
            $this->assertCount(count($expectedWebClientRequestDataCollection), $httpTransactions);

            foreach ($httpTransactions as $requestIndex => $httpTransaction) {
                /* @var EntityEnclosingRequestInterface $request */
                $request = $httpTransaction['request'];

                $this->assertEquals(
                    $expectedWebClientRequestDataCollection[$requestIndex],
                    $request->getPostFields()->getAll()
                );
            }
        }
    }
}
