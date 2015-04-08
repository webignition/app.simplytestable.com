<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Create\CreateAction\Success;

use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Create\CreateAction\CreateTest;
use Symfony\Component\HttpFoundation\Response;

class DefaultTest extends SuccessTest {

    protected function getRequestPostData() {
        return [
            'job-configuration' => 'foo',
            'schedule' => '* * * * *',
            'is-recurring' =>  '1'
        ];
    }

}