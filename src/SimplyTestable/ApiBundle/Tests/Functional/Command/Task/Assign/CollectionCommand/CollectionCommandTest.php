<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Task\Assign\CollectionCommand;

use SimplyTestable\ApiBundle\Tests\Functional\ConsoleCommandTestCase;

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