<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\User\GetAction;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;

class GetActionTest extends BaseControllerJsonTestCase {

    public function testGetForUserWithBasicPlan() {
        $email = 'user1@example.com';
        $password = 'password1';

        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());

        $this->assertEquals($email, $responseObject->email);
    }

    public function testGetForUserWithPremiumPlan() {
        $email = 'user1@example.com';
        $password = 'password1';

        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);

        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());
        $this->assertEquals($email, $responseObject->email);
    }
}


