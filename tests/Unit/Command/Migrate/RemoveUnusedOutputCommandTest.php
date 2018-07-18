<?php

namespace App\Tests\Unit\Command\Migrate;

use App\Command\Migrate\RemoveUnusedOutputCommand;
use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Services\ApplicationStateService;
use App\Tests\Factory\JobFactory;
use App\Tests\Factory\MockFactory;
use App\Tests\Factory\TaskOutputFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class RemoveUnusedOutputCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testRunCommandInMaintenanceReadOnlyMode()
    {
        $command = new RemoveUnusedOutputCommand(
            MockFactory::createApplicationStateService(true),
            MockFactory::createEntityManager()
        );

        $returnCode = $command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(
            RemoveUnusedOutputCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );

//        $applicationStateService = $this->container->get(ApplicationStateService::class);
//        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_READ_ONLY);
//
//        $returnCode = $this->command->run(new ArrayInput([]), new BufferedOutput());
//
//        $this->assertEquals(
//            RemoveUnusedOutputCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
//            $returnCode
//        );
//
//        $applicationStateService->setState(ApplicationStateService::STATE_ACTIVE);
    }
}
