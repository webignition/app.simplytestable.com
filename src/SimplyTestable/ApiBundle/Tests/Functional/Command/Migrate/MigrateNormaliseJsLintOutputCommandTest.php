<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Migrate;

use SimplyTestable\ApiBundle\Tests\Functional\ConsoleCommandTestCase;

class MigrateNormaliseJsLintOutputCommandTest extends ConsoleCommandTestCase {

    /**
     *
     * @return string
     */
    protected function getCommandName() {
        return 'simplytestable:normalise-jslint-output';
    }


    /**
     *
     * @return \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand[]
     */
    protected function getAdditionalCommands() {
        return array(
            new \SimplyTestable\ApiBundle\Command\MigrateNormaliseJsLintOutputCommand()
        );
    }

    public function testRunCommandInMaintenanceReadOnlyModeReturnsStatusCode1() {
        $this->executeCommand('simplytestable:maintenance:enable-read-only');
        $this->assertReturnCode(1);
    }

}
