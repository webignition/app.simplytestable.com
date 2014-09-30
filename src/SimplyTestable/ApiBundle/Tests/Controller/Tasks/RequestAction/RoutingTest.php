<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Tasks\RequestAction;

use SimplyTestable\ApiBundle\Tests\Controller\RoutingTest as BaseRoutingTest;

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
    protected function getRequestQueryData() {
        return [
            'worker_hostname' => 'worker.example.com',
            'worker_token' => 'foo'
        ];
    }


}