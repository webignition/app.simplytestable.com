<?php

namespace Tests\ApiBundle\Functional\EventListener\Stripe;

use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use SimplyTestable\ApiBundle\Services\TestHttpClientService;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

abstract class AbstractStripeEventListenerTest extends AbstractBaseTestCase
{
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
                    $request->getPostFields()->toArray()
                );
            }
        }
    }
}
