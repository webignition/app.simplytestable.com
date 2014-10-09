<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Worker\TaskNotificationCommand;

use SimplyTestable\ApiBundle\Command\Worker\TaskNotificationCommand;
use SimplyTestable\ApiBundle\Tests\Command\CommandTest as BaseCommandTest;

abstract class CommandTest extends BaseCommandTest {

    /**
     *
     * @return \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand[]
     */
    protected function getAdditionalCommands() {
        return array(
            new TaskNotificationCommand()
        );
    }

}