<?php

namespace App\Tests\Functional\Command\Stripe\Event;

use Mockery\Mock;
use Psr\Log\LoggerInterface;
use App\Command\Stripe\Event\ProcessCommand;
use App\Event\Stripe\DispatchableEvent;
use App\Services\StripeEventService;
use App\Services\UserAccountPlanService;
use App\Services\UserService;
use App\Tests\Factory\MockFactory;
use App\Tests\Services\StripeEventFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProcessCommandTest extends AbstractBaseTestCase
{
    /**
     * @var ProcessCommand
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = self::$container->get(ProcessCommand::class);
    }

    public function testRunForEventWithNoUser()
    {
        $stripeEventService = self::$container->get(StripeEventService::class);

        $stripeEvent = $stripeEventService->create(
            'stripe_id',
            'customer.subscription.created',
            false,
            json_encode([]),
            null
        );

        $returnCode = $this->command->run(new ArrayInput([
            'stripeId' => $stripeEvent->getStripeId(),
        ]), new BufferedOutput());

        $this->assertEquals(
            ProcessCommand::RETURN_CODE_EVENT_HAS_NO_USER,
            $returnCode
        );
    }

    /**
     * @dataProvider runDataProvider
     *
     * @param string $fixtureName
     * @param string $expectedEventName
     */
    public function testRun($fixtureName, $expectedEventName)
    {
        $userService = self::$container->get(UserService::class);
        $userAccountPlanService = self::$container->get(UserAccountPlanService::class);
        $entityManager = self::$container->get('doctrine.orm.entity_manager');

        /* @var LoggerInterface $logger */
        $logger = \Mockery::mock(LoggerInterface::class);

        $stripeCustomer = 'stripeCustomerId';

        $user = $userService->getPublicUser();
        $userAccountPlan = $userAccountPlanService->getForUser($user);
        $userAccountPlan->setStripeCustomer($stripeCustomer);

        $stripeEventFactory = self::$container->get(StripeEventFactory::class);
        $stripeEvent = $stripeEventFactory->createEvents([
            $fixtureName => [
                'data' => [
                    'object' => [
                        'customer' => $stripeCustomer,
                    ],
                ],
            ]
        ], $user);

        /* @var Mock|EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = \Mockery::mock(EventDispatcherInterface::class);
        $eventDispatcher
            ->shouldReceive('dispatch')
            ->withArgs([
                $expectedEventName,
                \Mockery::on(function ($argument) use ($stripeEvent) {
                    /* @var DispatchableEvent $argument */
                    if (!$argument instanceof DispatchableEvent) {
                        return false;
                    }

                    return $argument->getEntity() === $stripeEvent;
                }),
            ]);

        $command = new ProcessCommand(
            MockFactory::createApplicationStateService(),
            $entityManager,
            $logger,
            $eventDispatcher
        );

        $returnCode = $command->run(new ArrayInput([
            'stripeId' => $stripeEvent->getStripeId(),
        ]), new BufferedOutput());

        $this->assertEquals(
            ProcessCommand::RETURN_CODE_OK,
            $returnCode
        );
    }

    /**
     * @return array
     */
    public function runDataProvider()
    {
        return [
            'customer.subscription.created' => [
                'fixtureName' => 'customer.subscription.created.active',
                'expectedEventName' => 'stripe_process.customer.subscription.created',
            ],
            'customer.subscription.deleted' => [
                'fixtureName' => 'customer.subscription.deleted',
                'expectedEventName' => 'stripe_process.customer.subscription.deleted',
            ],
            'customer.subscription.trial_will_end' => [
                'fixtureName' => 'customer.subscription.trial_will_end',
                'expectedEventName' => 'stripe_process.customer.subscription.trial_will_end',
            ],
            'customer.subscription.updated' => [
                'fixtureName' => 'customer.subscription.updated.planchange',
                'expectedEventName' => 'stripe_process.customer.subscription.updated',
            ],
            'invoice.payment_failed' => [
                'fixtureName' => 'invoice.payment_failed',
                'expectedEventName' => 'stripe_process.invoice.payment_failed',
            ],
            'invoice.payment_succeeded' => [
                'fixtureName' => 'invoice.payment_succeeded',
                'expectedEventName' => 'stripe_process.invoice.payment_succeeded',
            ],
        ];
    }
}
