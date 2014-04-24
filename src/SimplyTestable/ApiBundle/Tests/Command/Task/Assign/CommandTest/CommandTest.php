<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Task\Assign\CommandTest;

use SimplyTestable\ApiBundle\Tests\ConsoleCommandTestCase;

abstract class CommandTest extends ConsoleCommandTestCase {
    
    /**
     * 
     * @return string
     */
    protected function getCommandName() {
        return 'simplytestable:task:assign';
    }
    
}
