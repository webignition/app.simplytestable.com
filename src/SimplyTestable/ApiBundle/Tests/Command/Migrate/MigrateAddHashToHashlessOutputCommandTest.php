<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Migrate;

use SimplyTestable\ApiBundle\Tests\ConsoleCommandTestCase;

class MigrateAddHashToHashlessOutputCommandTest extends ConsoleCommandTestCase {
    
    /**
     * 
     * @return string
     */
    protected function getCommandName() {
        return 'simplytestable:add-hash-to-hashless-output';
    }
    
    
    /**
     * 
     * @return \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand[]
     */
    protected function getAdditionalCommands() {        
        return array(
            new \SimplyTestable\ApiBundle\Command\Maintenance\EnableReadOnlyCommand(),
            new \SimplyTestable\ApiBundle\Command\MigrateAddHashToHashlessOutputCommand()
        );
    }     
    
    public function testRunCommandInMaintenanceReadOnlyModeReturnsStatusCode1() {
        $this->executeCommand('simplytestable:maintenance:enable-read-only');
        $this->assertReturnCode(1);
    }

}
