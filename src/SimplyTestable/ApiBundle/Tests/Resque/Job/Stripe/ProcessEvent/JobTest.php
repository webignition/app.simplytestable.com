<?php

namespace SimplyTestable\ApiBundle\Tests\Resque\Job\Stripe\ProcessEvent;

use SimplyTestable\ApiBundle\Tests\Resque\Job\JobTest as BaseJobTest;

class JobTest extends BaseJobTest {

    protected function getArgs() {
        return [
            'stripeId' => 'foo123'
        ];
    }


    protected function getExpectedQueue() {
        return 'stripe-event';
    }

}
