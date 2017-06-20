<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Task\Assign\SelectedCommand;

use SimplyTestable\ApiBundle\Tests\Functional\ConsoleCommandTestCase;

abstract class CommandTest extends ConsoleCommandTestCase {

    /**
     *
     * @return string
     */
    protected function getCommandName() {
        return 'simplytestable:task:assign-selected';
    }


    /**
     *
     * @return \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand[]
     */
    protected function getAdditionalCommands() {
        return array(
            new \SimplyTestable\ApiBundle\Command\Task\Assign\SelectedCommand()
        );
    }
}