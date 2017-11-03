<?php

namespace Tests\ApiBundle\Functional\Services\TeamInvite;

use Tests\ApiBundle\Functional\AbstractBaseTestCase;

abstract class ServiceTest extends AbstractBaseTestCase {

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
