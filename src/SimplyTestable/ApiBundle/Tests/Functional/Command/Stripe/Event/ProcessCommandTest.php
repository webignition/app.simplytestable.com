<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Stripe\Event;

use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use SimplyTestable\ApiBundle\Command\Stripe\Event\ProcessCommand;
use SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Tests\Factory\StripeEventFactory;
use SimplyTestable\ApiBundle\Tests\Functional\AbstractBaseTestCase;
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
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
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
        $stripeEventService = $this->container->get('simplytestable.services.stripeeventservice');

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
        $userService = $this->container->get('simplytestable.services.userservice');
        $userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');

        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
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

        /* @var MockInterface|EventDispatcherInterface $eventDispatcher */
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
