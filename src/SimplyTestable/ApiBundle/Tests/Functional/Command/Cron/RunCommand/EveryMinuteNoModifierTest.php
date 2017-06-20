<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Cron\RunCommand;

class EveryMinuteNoModifierTest extends IsRunTest {

    protected function getSchedule()
    {
        return '* * * * *';
    }

    protected function getModifier()
    {
        return null;
    }
}
