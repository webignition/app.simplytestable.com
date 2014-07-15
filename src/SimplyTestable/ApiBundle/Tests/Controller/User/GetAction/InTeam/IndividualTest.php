<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\User\GetAction\InTeam;

class IndividualTest extends InTeamTest {

    public function getUser() {
        return $this->createAndActivateUser('user@example.com');
    }


    public function getExpectedUserInTeam() {
        return false;
    }
}


