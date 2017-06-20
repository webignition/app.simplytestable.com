<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Worker\Tasks\RequestAction;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\RoutingTest as BaseRoutingTest;

class RoutingTest extends BaseRoutingTest {


    /**
     *
     * @return array
     */
    protected function getRouteParameters() {
        return [];
    }


    /**
     * @return array
     */
    protected function getRequestPostData() {
        return [
            'worker_hostname' => 'worker.example.com',
            'worker_token' => 'foo'
        ];
    }


}