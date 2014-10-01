<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Task\Assign\CollectionCommand;

use SimplyTestable\ApiBundle\Tests\ConsoleCommandTestCase;

abstract class CollectionCommandTest extends ConsoleCommandTestCase {
    
    const CANONICAL_URL = 'http://example.com/';
    
    /**
     * 
     * @return string
     */
    protected function getCommandName() {
        return 'simplytestable:task:assigncollection';
    }

}