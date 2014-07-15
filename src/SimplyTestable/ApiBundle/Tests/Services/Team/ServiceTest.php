<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Team;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

abstract class ServiceTest extends BaseSimplyTestableTestCase {

    /**
     * @return \SimplyTestable\ApiBundle\Services\Team\MemberService
     */
    protected function getTeamMemberService() {
        return $this->container->get('simplytestable.services.teammemberservice');
    }


    /**
     * @return \SimplyTestable\ApiBundle\Services\Team\Service
     */
    protected function getTeamService() {
        return $this->container->get('simplytestable.services.teamservice');
    }

}