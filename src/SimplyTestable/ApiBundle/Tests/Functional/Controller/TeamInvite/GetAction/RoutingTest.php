<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\TeamInvite\GetAction;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\RoutingTest as BaseRoutingTest;

class RoutingTest extends BaseRoutingTest {


    /**
     *
     * @return array
     */
    protected function getRouteParameters() {
        return [
            'invitee_email' => 'user@example.com'
        ];
    }

}