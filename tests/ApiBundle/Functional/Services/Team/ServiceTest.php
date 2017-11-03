<?php

namespace Tests\ApiBundle\Functional\Services\Team;

use Tests\ApiBundle\Functional\AbstractBaseTestCase;

abstract class ServiceTest extends AbstractBaseTestCase {

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
