<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job\ResolveWebsiteCommand;

use SimplyTestable\ApiBundle\Tests\ConsoleCommandTestCase;
 
abstract class CommandTest extends ConsoleCommandTestCase {
    
    const CANONICAL_URL = 'http://example.com';
    
    /**
     * 
     * @return string
     */
    protected function getCommandName() {
        return 'simplytestable:job:resolve';
    }
    
    
    /**
     * 
     * @return \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand[]
     */
    protected function getAdditionalCommands() {        
        return array(
            new \SimplyTestable\ApiBundle\Command\Job\ResolveWebsiteCommand()
        );
    }
}
