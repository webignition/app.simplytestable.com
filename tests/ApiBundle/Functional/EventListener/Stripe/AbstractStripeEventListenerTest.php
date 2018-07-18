<?php

namespace Tests\ApiBundle\Functional\EventListener\Stripe;

use Psr\Http\Message\RequestInterface;
use SimplyTestable\ApiBundle\Services\HttpClientService;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Tests\ApiBundle\Services\TestHttpClientService;

abstract class AbstractStripeEventListenerTest extends AbstractBaseTestCase
{
    /**
     * @var TestHttpClientService
     */
    protected $httpClientService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->httpClientService = self::$container->get(HttpClientService::class);
    }

    /**
     * @param array $expectedWebClientRequestDataCollection
     */
    protected function assertWebClientRequests(array $expectedWebClientRequestDataCollection)
    {
        $httpTransactions = $this->httpClientService->getHistory();

        if (empty($expectedWebClientRequestDataCollection)) {
            $this->assertEmpty($httpTransactions);
        } else {
            $this->assertCount(count($expectedWebClientRequestDataCollection), $httpTransactions);

            foreach ($httpTransactions as $requestIndex => $httpTransaction) {
                /* @var RequestInterface $request */
                $request = $httpTransaction['request'];

                $postedData = [];
                parse_str($request->getBody()->getContents(), $postedData);


                $this->assertEquals(
                    $expectedWebClientRequestDataCollection[$requestIndex],
                    $postedData
                );
            }
        }
    }
}
