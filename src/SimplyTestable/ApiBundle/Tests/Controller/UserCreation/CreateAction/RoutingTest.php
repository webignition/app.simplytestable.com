<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\UserCreation\CreateAction;

use SimplyTestable\ApiBundle\Tests\Controller\RoutingTest as BaseRoutingTest;

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
            'email' => 'user@example.com',
            'password' => 'password'
        ];
    }

}