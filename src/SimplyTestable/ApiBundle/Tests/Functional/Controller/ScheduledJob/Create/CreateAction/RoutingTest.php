<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Create\CreateAction;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\RoutingTest as BaseRoutingTest;

class RoutingTest extends BaseRoutingTest {

    /**
     *
     * @return array
     */
    protected function getRouteParameters() {
        return [];
    }


    protected function getRequestPostData() {
        return [
            'job-configuration' => 'foo',
            'schedule' => '* * * * *',
            'is-recurring' =>  '1'
        ];
    }

}