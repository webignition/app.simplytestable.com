<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Stripe\Event;

use phpmock\mockery\PHPMockery;
use SimplyTestable\ApiBundle\Command\Stripe\Event\UpdateDataCommand;
use SimplyTestable\ApiBundle\Entity\Stripe\Event as StripeEvent;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Tests\Factory\StripeEventFactory;
use SimplyTestable\ApiBundle\Tests\Functional\AbstractBaseTestCase;
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
     * @param string|bool $expectedStripeEventData
     */
    public function testRun($stripeApiResponses, $args, $expectedStripeEventData)
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

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

        $stripeEventRepository = $entityManager->getRepository(StripeEvent::class);
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
