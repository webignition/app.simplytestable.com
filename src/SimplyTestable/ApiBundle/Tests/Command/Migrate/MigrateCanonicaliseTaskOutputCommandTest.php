<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Migrate;

use SimplyTestable\ApiBundle\Tests\ConsoleCommandTestCase;

class MigrateCanonicaliseTaskOutputCommandTest extends ConsoleCommandTestCase {        
    
    /**
     * 
     * @return string
     */
    protected function getCommandName() {
        return 'simplytestable:migrate:canonicalise-task-output';
    }
    
    
    /**
     * 
     * @return \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand[]
     */
    protected function getAdditionalCommands() {        
        return array(
            new \SimplyTestable\ApiBundle\Command\Maintenance\EnableReadOnlyCommand(),
            new \SimplyTestable\ApiBundle\Command\MigrateCanonicaliseTaskOutputCommand()
        );
    }     
    
    public function testRunCommandInMaintenanceReadOnlyModeReturnsStatusCode1() {
        $this->executeCommand('simplytestable:maintenance:enable-read-only');
        $this->assertReturnCode(1);
    }

}
