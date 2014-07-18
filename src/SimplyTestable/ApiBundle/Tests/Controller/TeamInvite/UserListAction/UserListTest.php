<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\TeamInvite\UserListAction;

use SimplyTestable\ApiBundle\Tests\Controller\TeamInvite\ActionTest;

class UserListTest extends ActionTest {

    public function testNoInvitesReturnsEmptyCollection() {
        $leader = $this->createAndActivateUser('leader@example.com');
        $this->getUserService()->setUser($leader);

        $this->getTeamService()->create(
            'Foo',
            $leader
        );

        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController()->$methodName();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], json_decode($response->getContent(), true));
    }

    public function testHasInvitesReturnsInviteCollection() {
        $leader1 = $this->createAndActivateUser('leader1@example.com');
        $leader2 = $this->createAndActivateUser('leader2@example.com');
        $user = $this->createAndActivateUser('user@example.com');

        $this->getUserService()->setUser($user);

        $this->getTeamService()->create(
            'Foo1',
            $leader1
        );

        $this->getTeamService()->create(
            'Foo2',
            $leader2
        );

        $invite1 = $this->getTeamInviteService()->get($leader1, $user);
        $invite2 = $this->getTeamInviteService()->get($leader2, $user);

        $methodName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController()->$methodName();

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals([
            [
                'user' => $invite1->getUser()->getUsername(),
                'token' => $invite1->getToken()
            ],
            [
                'user' => $invite2->getUser()->getUsername(),
                'token' => $invite2->getToken()
            ]
        ], json_decode($response->getContent(), true));
    }


    /**
     *
     * @return array
     */
    protected function getRouteParameters() {
        return [];
    }
    
}