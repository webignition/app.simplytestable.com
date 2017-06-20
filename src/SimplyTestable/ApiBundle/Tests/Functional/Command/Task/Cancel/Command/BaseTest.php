<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Task\Cancel\Command;

use SimplyTestable\ApiBundle\Tests\Functional\ConsoleCommandTestCase;

abstract class BaseTest extends ConsoleCommandTestCase {

    /**
     *
     * @return string
     */
    protected function getCommandName() {
        return 'simplytestable:task:cancel';
    }


    /**
     *
     * @return \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand[]
     */
    protected function getAdditionalCommands() {
        return array(
            new \SimplyTestable\ApiBundle\Command\Task\Cancel\Command()
        );
    }

}
