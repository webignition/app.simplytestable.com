<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Stripe\Event;

use Mockery\MockInterface;
use phpmock\mockery\PHPMockery;
use Psr\Log\LoggerInterface;
use SimplyTestable\ApiBundle\Command\Stripe\Event\ProcessCommand;
use SimplyTestable\ApiBundle\Command\Stripe\Event\UpdateDataCommand;
use SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\StripeEventFactory;
use SimplyTestable\ApiBundle\Tests\Factory\StripeEventFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UpdateDataCommandTest extends BaseSimplyTestableTestCase
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

        $this->command = $this->container->get('simplytestable.command.stripe.event.updatedata');
    }

    public function testRunInMaintenanceReadOnlyMode()
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_READ_ONLY);

        $returnCode = $this->command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(
            UpdateDataCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );

        $applicationStateService->setState(ApplicationStateService::STATE_ACTIVE);
    }

    /**
     * @dataProvider runDataProvider
     *
     * @param array $stripeApiResponses
     * @param array $args
     */
    public function testRunFoo($stripeApiResponses, $args, $expectedStripeEventData)
    {
        $eventDispatcher = $this->container->get('event_dispatcher');
        $httpClientService = $this->container->get('simplytestable.services.httpclientservice');
        $stripeService = $this->container->get('simplytestable.services.stripeservice');
        $userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');
        $userService = $this->container->get('simplytestable.services.userservice');
        $stripeEventService = $this->container->get('simplytestable.services.stripeeventservice');

        $user = $userService->getPublicUser();

        $stripeEventFactory = new StripeEventFactory($this->container);
        $stripeEvent = $stripeEventFactory->createEvents([
            'customer.subscription.created.active' => [],
        ], $user);

        PHPMockery::mock(
            'SimplyTestable\ApiBundle\Command\Stripe\Event',
            'shell_exec'
        )->andReturnValues($stripeApiResponses);

        $returnCode = $this->command->run(new ArrayInput($args), new BufferedOutput());

        $this->assertEquals(
            UpdateDataCommand::RETURN_CODE_OK,
            $returnCode
        );

        $updatedStripeEvent = $stripeEventService->getByStripeId($stripeEvent->getStripeId());

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
