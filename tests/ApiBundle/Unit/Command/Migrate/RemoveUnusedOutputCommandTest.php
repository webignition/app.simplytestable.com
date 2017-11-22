<?php

namespace Tests\ApiBundle\Unit\Command\Migrate;

use SimplyTestable\ApiBundle\Command\Migrate\RemoveUnusedOutputCommand;
use SimplyTestable\ApiBundle\Entity\Task\Output;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\MockFactory;
use Tests\ApiBundle\Factory\TaskOutputFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class RemoveUnusedOutputCommandTest extends \PHPUnit_Framework_TestCase
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
