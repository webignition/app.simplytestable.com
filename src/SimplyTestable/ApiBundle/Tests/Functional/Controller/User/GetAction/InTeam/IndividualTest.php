<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\User\GetAction\InTeam;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class IndividualTest extends InTeamTest
{
    public function getUser()
    {
        $userFactory = new UserFactory($this->container);

        return $userFactory->createAndActivateUser();
    }

    public function getExpectedUserInTeam()
    {
        return false;
    }
}
