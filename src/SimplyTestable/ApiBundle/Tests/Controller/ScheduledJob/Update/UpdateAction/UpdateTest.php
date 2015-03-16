<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Update\UpdateAction;

use SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Update\ActionTest;

abstract class UpdateTest extends ActionTest {

    /**
     *
     * @return array
     */
    protected function getRouteParameters() {
        return [
            'id' => 1
        ];
    }

}