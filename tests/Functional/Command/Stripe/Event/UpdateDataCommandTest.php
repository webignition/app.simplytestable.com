<?php

namespace App\Tests\Functional\Command\Stripe\Event;

use phpmock\mockery\PHPMockery;
use App\Command\Stripe\Event\UpdateDataCommand;
use App\Entity\Stripe\Event;
use App\Services\UserService;
use App\Tests\Factory\StripeEventFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class UpdateDataCommandTest extends AbstractBaseTestCase
{
    /**
     * @var UpdateDataCommand
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = self::$container->get(UpdateDataCommand::class);
    }

    /**
     * @dataProvider runDataProvider
     *
     * @param array $stripeApiResponses
     * @param array $args
     * @param string|bool $expectedStripeEventData
     */
    public function testRun($stripeApiResponses, $args, $expectedStripeEventData)
    {
        $userService = self::$container->get(UserService::class);
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $stripeEventRepository = $entityManager->getRepository(Event::class);

        $user = $userService->getPublicUser();

        $stripeEventFactory = new StripeEventFactory(self::$container);
        $stripeEvent = $stripeEventFactory->createEvents([
            'customer.subscription.created.active' => [],
        ], $user);

        PHPMockery::mock(
            'App\Command\Stripe\Event',
            'shell_exec'
        )->andReturnValues($stripeApiResponses);

        $returnCode = $this->command->run(new ArrayInput($args), new BufferedOutput());

        $this->assertEquals(
            UpdateDataCommand::RETURN_CODE_OK,
            $returnCode
        );

        $updatedStripeEvent = $stripeEventRepository->findOneBy([
            'stripeId' => $stripeEvent->getStripeId(),
        ]);

        if ($expectedStripeEventData === true) {
            $this->assertEquals($stripeEvent->getStripeEventData(), $updatedStripeEvent->getStripeEventData());
        } else {
            $this->assertEquals($expectedStripeEventData, $updatedStripeEvent->getStripeEventData());
        }

        \Mockery::close();
    }

    /**
     * @return array
     */
    public function runDataProvider()
    {
        return [
            'invalid request' => [
                'stripeApiResponses' => [
                    json_encode([
                        'error' => [
                            'type' => 'invalid_request_error',
                            'message' => 'No such event: evt_00000000000000',
                            'param' => 'id',
                        ],
                    ]),
                ],
                'args' => [],
                'expectedStripeEventData' => true,
            ],
            'null response' => [
                'stripeApiResponses' => [
                    null,
                ],
                'args' => [],
                'expectedStripeEventData' => true,
            ],
            'valid response' => [
                'stripeApiResponses' => [
                    json_encode('foo'),
                ],
                'args' => [],
                'expectedStripeEventData' => '"foo"',
            ],
            'valid response; dry-run' => [
                'stripeApiResponses' => [
                    json_encode('foo'),
                ],
                'args' => [
                    '--dry-run' => true,
                ],
                'expectedStripeEventData' => true,
            ],
        ];
    }
}
