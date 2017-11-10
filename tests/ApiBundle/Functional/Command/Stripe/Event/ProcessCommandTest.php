<?php

namespace Tests\ApiBundle\Functional\Command\Stripe\Event;

use Mockery\Mock;
use Psr\Log\LoggerInterface;
use SimplyTestable\ApiBundle\Command\Stripe\Event\ProcessCommand;
use SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\StripeEventService;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use SimplyTestable\ApiBundle\Services\UserService;
use Tests\ApiBundle\Factory\StripeEventFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
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

        $this->command = $this->container->get('simplytestable.command.stripe.event.process');
    }

    public function testRunInMaintenanceReadOnlyMode()
    {
        $applicationStateService = $this->container->get(ApplicationStateService::class);
        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_READ_ONLY);

        $returnCode = $this->command->run(new ArrayInput([
            'stripeId' => 1,
        ]), new BufferedOutput());

        $this->assertEquals(
            ProcessCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );

        $applicationStateService->setState(ApplicationStateService::STATE_ACTIVE);
    }

    public function testRunForEventWithNoUser()
    {
        $stripeEventService = $this->container->get(StripeEventService::class);

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
    public function testRunFoo($fixtureName, $expectedEventName)
    {
        $userService = $this->container->get(UserService::class);
        $userAccountPlanService = $this->container->get(UserAccountPlanService::class);
        $applicationStateService = $this->container->get(ApplicationStateService::class);
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        /* @var LoggerInterface $logger */
        $logger = \Mockery::mock(LoggerInterface::class);

        $stripeCustomer = 'stripeCustomerId';

        $user = $userService->getPublicUser();
        $userAccountPlan = $userAccountPlanService->getForUser($user);
        $userAccountPlan->setStripeCustomer($stripeCustomer);

        $stripeEventFactory = new StripeEventFactory($this->container);
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
            $applicationStateService,
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
