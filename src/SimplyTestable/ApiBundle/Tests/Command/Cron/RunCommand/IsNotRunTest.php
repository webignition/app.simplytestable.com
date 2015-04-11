<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Cron\RunCommand;

abstract class IsNotRunTest extends CommandTest {

    protected function getExpectedCronJobReturnCode() {
        return 1;
    }

    protected function getExpectedCommandOutput()
    {
        return '';
    }


}
