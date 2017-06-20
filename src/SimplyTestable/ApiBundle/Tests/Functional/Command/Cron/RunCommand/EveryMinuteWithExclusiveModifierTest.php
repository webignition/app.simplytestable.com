<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Cron\RunCommand;

class EveryMinuteWithExclusiveModifierTest extends IsNotRunTest {

    protected function getSchedule()
    {
        return '* * * * *';
    }

    /**
     * This modifier limits execution to days of the month
     * less than or equal to 0 - no day of the month
     * falls into this range
     *
     * @return string
     */
    protected function getModifier()
    {
        return '[ `date +\%d` -le 0 ]';
    }

}
