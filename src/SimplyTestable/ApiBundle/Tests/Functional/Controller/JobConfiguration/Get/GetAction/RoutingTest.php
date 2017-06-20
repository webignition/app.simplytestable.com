<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Get\GetAction;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\RoutingTest as BaseRoutingTest;

class RoutingTest extends BaseRoutingTest {

    /**
     *
     * @return array
     */
    protected function getRouteParameters() {
        return [
            'label' => 'foo'
        ];
    }

}