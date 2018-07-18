<?php

namespace App\Tests\Unit\Command\Stripe\Event;

use phpmock\mockery\PHPMockery;
use App\Command\Stripe\Event\UpdateDataCommand;
use App\Entity\Stripe\Event;
use App\Services\ApplicationStateService;
use App\Services\UserService;
use App\Tests\Factory\MockFactory;
use App\Tests\Factory\StripeEventFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class UpdateDataCommandTest extends \PHPUnit\Framework\TestCase
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
