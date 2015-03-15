<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Create\CreateAction\MissingInput;

class ScheduleTest extends MissingInputTest {

    protected function getMissingRequestValueKey() {
        return 'schedule';
    }
}