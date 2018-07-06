<?php

namespace Tests\ApiBundle\Unit\Command\User;

use SimplyTestable\ApiBundle\Command\User\AddNonPlannedUsersToBasicPlanCommand;
use Tests\ApiBundle\Factory\MockFactory;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class AddNonPlannedUsersToBasicPlanCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testRunInMaintenanceReadOnlyModeReturnsStatusCode1()
    {
        $command = new AddNonPlannedUsersToBasicPlanCommand(
            MockFactory::createApplicationStateService(true),
            MockFactory::createUserAccountPlanService(),
            MockFactory::createEntityManager(),
            MockFactory::createAccountPlanService()
        );

        $returnCode = $command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(
            AddNonPlannedUsersToBasicPlanCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );
    }
}
