<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Create\CreateAction\Success;

use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Create\CreateAction\CreateTest;
use Symfony\Component\HttpFoundation\Response;

class FooTest extends SuccessTest {

    const CRON_MODIFIER = '[ `date +\%d` -le 7 ]';

    protected function getRequestPostData() {
        return [
            'job-configuration' => 'foo',
            'schedule' => '* * * * *',
            'is-recurring' =>  '1',
            'schedule-modifier' => self::CRON_MODIFIER
        ];
    }


    public function testScheduleModifierIsSaved() {
        $this->assertEquals(self::CRON_MODIFIER, $this->scheduledJob->getCronModifier());
    }

}