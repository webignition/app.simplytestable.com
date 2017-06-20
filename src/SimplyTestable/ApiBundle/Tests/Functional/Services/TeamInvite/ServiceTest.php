<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\TeamInvite;

use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

abstract class ServiceTest extends BaseSimplyTestableTestCase {

    /**
     * @return \SimplyTestable\ApiBundle\Services\Team\InviteService
     */
    protected function getTeamInviteService() {
        return $this->container->get('simplytestable.services.teaminviteservice');
    }


    /**
     * @return \SimplyTestable\ApiBundle\Services\Team\Service
     */
    protected function getTeamService() {
        return $this->container->get('simplytestable.services.teamservice');
    }

}
