<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Cron\RunCommand;

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
