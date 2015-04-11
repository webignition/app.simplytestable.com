<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Cron\RunCommand;

class EveryMinuteWithInclusiveModifierTest extends IsRunTest {

    protected function getSchedule()
    {
        return '* * * * *';
    }

    /**
     * This modifier limits execution to days of the month
     * less than or equal to 40 - every day of the month
     * falls into this range
     *
     * @return string
     */
    protected function getModifier()
    {
        return '[ `date +\%d` -le 40 ]';
    }
}
