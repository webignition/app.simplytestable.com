<?php

namespace App\Tests\Unit\Controller\Stripe;

use App\Controller\Stripe\WebHookController;
use App\Repository\UserAccountPlanRepository;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\Tests\Factory\MockFactory;
use Symfony\Component\HttpFoundation\Request;

/**
 * @group Controller/Stripe/WebHookController
 */
class WebHookControllerTest extends \PHPUnit\Framework\TestCase
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
            MockFactory::createStripeWebHookMailNotificationSender(),
            \Mockery::mock(UserAccountPlanRepository::class),
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
