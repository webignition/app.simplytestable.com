<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Update\UpdateAction;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Update\ActionTest;

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