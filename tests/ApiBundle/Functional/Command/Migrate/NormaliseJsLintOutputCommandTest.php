<?php

namespace Tests\ApiBundle\Functional\Command\Migrate;

use SimplyTestable\ApiBundle\Command\Migrate\NormaliseJsLintOutputCommand;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class NormaliseJsLintOutputCommandTest extends AbstractBaseTestCase
{
    /**
     * @var NormaliseJsLintOutputCommand
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = $this->container->get(NormaliseJsLintOutputCommand::class);
    }

    public function testRunCommandInMaintenanceReadOnlyModeReturnsStatusCode1()
    {
        $applicationStateService = $this->container->get(ApplicationStateService::class);
        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_READ_ONLY);

        $returnCode = $this->command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(
            NormaliseJsLintOutputCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );

        $applicationStateService->setState(ApplicationStateService::STATE_ACTIVE);
    }
}
