<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Update\UpdateAction;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\RoutingTest as BaseRoutingTest;

class RoutingTest extends BaseRoutingTest {

    protected function setUp() {
        parent::setUp();
        $this->getRouter()->getContext()->setMethod('POST');
    }

    /**
     *
     * @return array
     */
    protected function getRouteParameters() {
        return [
            'id' => '1'
        ];
    }

}