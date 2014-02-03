<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job\ResolveWebsiteCommand;

use SimplyTestable\ApiBundle\Tests\ConsoleCommandTestCase;
 
abstract class CommandTest extends ConsoleCommandTestCase {
    
    /**
     * 
     * @return string
     */
    protected function getCommandName() {
        return 'simplytestable:job:resolve';
    }
}
