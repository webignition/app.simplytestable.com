<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Job\ResolveWebsiteCommand;

use SimplyTestable\ApiBundle\Tests\Functional\ConsoleCommandTestCase;

abstract class CommandTest extends ConsoleCommandTestCase {

    /**
     *
     * @return string
     */
    protected function getCommandName() {
        return 'simplytestable:job:resolve';
    }
}
