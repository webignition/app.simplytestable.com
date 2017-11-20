<?php

namespace Tests\ApiBundle\Unit\Controller\Stripe;

use SimplyTestable\ApiBundle\Controller\Stripe\WebHookController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Tests\ApiBundle\Factory\MockFactory;
use Symfony\Component\HttpFoundation\Request;

/**
 * @group Controller/Stripe/WebHookController
 */
class WebHookControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider indexActionNoEventContentDataProvider
     *
     * @param array $postData
     * @param string $requestContent
     */
    public function testIndexActionNoEventContent($postData, $requestContent)
    {
        $request = new Request(
            [],
            $postData,
            [],
            [],
            [],
            [],
            $requestContent
        );

        $webHookController = new WebHookController();

        $this->expectException(BadRequestHttpException::class);

        $webHookController->indexAction(
            MockFactory::createEntityManager(),
            MockFactory::createStripeEventService(),
            MockFactory::createResqueQueueService(),
            MockFactory::createResqueJobFactory(),
            MockFactory::createStripeWebHookMailNotificationSender(),
            $request
        );
    }

    /**
     * @return array
     */
    public function indexActionNoEventContentDataProvider()
    {
        return [
            'empty request' => [
                'postData' => [],
                'requestContent' => '',
            ],
            'request content is not json' => [
                'postData' => [],
                'requestContent' => '{id}',
            ],
            'request content lacks object' => [
                'postData' => [],
                'requestContent' => json_encode([
                    'foo' => 'bar',
                ]),
            ],
            'event parameter is not json' => [
                'postData' => [
                    'event' => '{id}',
                ],
                'requestContent' => '',
            ],
            'event parameter lacks object' => [
                'postData' => [
                    'event' => json_encode([
                        'foo' => 'bar',
                    ]),
                ],
                'requestContent' => '',
            ],
        ];
    }
}
