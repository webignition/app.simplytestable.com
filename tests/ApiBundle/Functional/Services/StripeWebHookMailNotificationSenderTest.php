<?php

namespace Tests\ApiBundle\Functional\Services;

use GuzzleHttp\Psr7\Response;
use Mockery\Mock;
use Postmark\Models\DynamicResponseModel;
use Postmark\PostmarkClient;
use Psr\Log\LoggerInterface;
use SimplyTestable\ApiBundle\Services\HttpClientService;
use SimplyTestable\ApiBundle\Services\StripeWebHookMailNotificationSender;
use Tests\ApiBundle\Factory\HttpFixtureFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Tests\ApiBundle\Services\TestHttpClientService;

class StripeWebHookMailNotificationSenderTest extends AbstractBaseTestCase
{
    /**
     * @var PostmarkClient
     */
    private $postmarkClient;

    /**
     * @var TestHttpClientService
     */
    private $httpClientService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->postmarkClient = self::$container->get(PostmarkClient::class);
        $this->httpClientService = self::$container->get(HttpClientService::class);
    }

    public function testSendSuccess()
    {
        $this->httpClientService->appendFixtures([
            HttpFixtureFactory::createPostmarkResponse(200, [
                'To' => 'user@example.com',
                'SubmittedAt' => '2014-02-17T07:25:01.4178645-05:00',
                'MessageId' => '0a129aee-e1cd-480d-b08d-4f48548ff48d',
                'ErrorCode' => 0,
                'Message' => 'OK',
            ]),
        ]);

        /* @var Mock|LoggerInterface $logger */
        $logger = \Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldNotReceive('error');

        $sender = $this->createStripeWebHookMailNotificationSender(
            $this->postmarkClient,
            $logger
        );

        $response = $sender->send('{}', 'Stripe Webhook Data customer.created');

        $this->assertInstanceOf(DynamicResponseModel::class, $response);
        $this->assertEquals(0, $response->__get('ErrorCode'));
    }

    /**
     * @dataProvider sendFailureDataProvider
     *
     * @param array $httpFixtures
     * @param string $expectedLoggerMessage
     */
    public function testSendFailure(array $httpFixtures, $expectedLoggerMessage)
    {
        $this->httpClientService->appendFixtures($httpFixtures);

        /* @var Mock|LoggerInterface $logger */
        $logger = \Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('error')
            ->once()
            ->withArgs(function ($message, array $context) use ($expectedLoggerMessage) {
                $this->assertEquals($expectedLoggerMessage, $message);
                $this->assertArrayHasKey('message', $context);
                $this->assertNotEmpty($context['message']);

                return true;
            });

        $sender = $this->createStripeWebHookMailNotificationSender(
            $this->postmarkClient,
            $logger
        );

        $response = $sender->send('{}', 'Stripe Webhook Data customer.created');

        $this->assertNull($response);
    }

    /**
     * @return array
     */
    public function sendFailureDataProvider()
    {
        return [
            'HTTP 401 Unauthorized (missing or incorrect API token)' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createPostmarkResponse(401, [
                        'ErrorCode' => 10,
                        'Message' => 'foo'
                    ]),
                ],
                'expectedLoggerMessage' => 'Postmark failure [401] []',
            ],
        ];
    }

    /**
     * @param PostmarkClient $postmarkClient
     * @param LoggerInterface $logger
     *
     * @return StripeWebHookMailNotificationSender
     */
    private function createStripeWebHookMailNotificationSender(
        PostmarkClient $postmarkClient,
        LoggerInterface $logger
    ) {
        return new StripeWebHookMailNotificationSender(
            $postmarkClient,
            $logger,
            self::$container->getParameter('stripe_webhook_developer_notification')
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
