<?php

namespace Tests\ApiBundle\Functional\EventListener\Stripe;

use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Post\PostBody;
use SimplyTestable\ApiBundle\Services\HttpClientService;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

abstract class AbstractStripeEventListenerTest extends AbstractBaseTestCase
{
    /**
     * @param HttpClientService $httpClientService
     * @param array $expectedWebClientRequestDataCollection
     */
    protected function assertWebClientRequests(
        HttpClientService $httpClientService,
        $expectedWebClientRequestDataCollection
    ) {
        $httpTransactions = $httpClientService->getHistory();

        if (empty($expectedWebClientRequestDataCollection)) {
            $this->assertEmpty($httpTransactions);
        } else {
            $this->assertCount(count($expectedWebClientRequestDataCollection), $httpTransactions);

            foreach ($httpTransactions as $requestIndex => $httpTransaction) {
                /* @var RequestInterface $request */
                $request = $httpTransaction['request'];

                /* @var PostBody $requestBody */
                $requestBody = $request->getBody();

                $this->assertEquals(
                    $expectedWebClientRequestDataCollection[$requestIndex],
                    $requestBody->getFields()
                );
            }
        }
    }
}
