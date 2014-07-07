<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Team;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

abstract class ServiceTest extends BaseSimplyTestableTestCase {


    /**
     * @return \SimplyTestable\ApiBundle\Services\TeamService
     */
    protected function getTeamService() {
        return $this->container->get('simplytestable.services.teamservice');
    }

}
