<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\JobConfiguration\Create\CreateAction;

use SimplyTestable\ApiBundle\Tests\Controller\RoutingTest as BaseRoutingTest;

class RoutingTest extends BaseRoutingTest {
    
    /**
     * 
     * @return array
     */
    protected function getRouteParameters() {
        return array();
    }


    protected function getRequestPostData() {
        return [
            'website' => 'http://example.com/',
            'type' => 'Full site',
            'task-configuration' => [
                'HTML validation' => [],
                'CSS validation' => []
            ],
            'parameters' => '',
            'label' => 'foo'
        ];
    }

}