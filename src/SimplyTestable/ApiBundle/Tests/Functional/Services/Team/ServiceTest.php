<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Team;

use SimplyTestable\ApiBundle\Tests\Functional\AbstractBaseTestCase;

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
