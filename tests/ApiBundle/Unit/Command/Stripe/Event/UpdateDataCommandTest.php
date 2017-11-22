<?php

namespace Tests\ApiBundle\Unit\Command\Stripe\Event;

use phpmock\mockery\PHPMockery;
use SimplyTestable\ApiBundle\Command\Stripe\Event\UpdateDataCommand;
use SimplyTestable\ApiBundle\Entity\Stripe\Event;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\UserService;
use Tests\ApiBundle\Factory\MockFactory;
use Tests\ApiBundle\Factory\StripeEventFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class UpdateDataCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testRunInMaintenanceReadOnlyMode()
    {
        $command = new UpdateDataCommand(
            MockFactory::createApplicationStateService(true),
            MockFactory::createStripeEventService(),
            MockFactory::createEntityManager(),
            'stripe key'
        );

        $returnCode = $command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(
            UpdateDataCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );

//        $applicationStateService = $this->container->get(ApplicationStateService::class);
//        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_READ_ONLY);
//
//        $returnCode = $this->command->run(new ArrayInput([]), new BufferedOutput());
//
//        $this->assertEquals(
//            UpdateDataCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
//            $returnCode
//        );
//
//        $applicationStateService->setState(ApplicationStateService::STATE_ACTIVE);
    }
}
