<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Cron\RunCommand;

abstract class IsRunTest extends CommandTest {

    protected function getExpectedCronJobReturnCode() {
        return 0;
    }

    protected function getExpectedCommandOutput() {
        return implode("\n", [
            'simplytestable:scheduledjob:enqueue [' . $this->scheduledJob->getId(). '] start',
            'simplytestable:scheduledjob:enqueue [' . $this->scheduledJob->getId(). '] done'
        ]);
    }


}
