<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Tasks\RequestAction;

use SimplyTestable\ApiBundle\Tests\Controller\Tasks\ActionTest;

abstract class RequestTest extends ActionTest {

    /**
     * @return array
     */
    protected function getRequestQueryData() {
        return [
            'worker_hostname' => 'worker.example.com',
            'worker_token' => 'foo'
        ];
    }

//
//    public function testInMaintenanceModeReturns503() {
//        $this->executeCommand('simplytestable:maintenance:enable-read-only');
//        $this->assertEquals(503, $this->getJobStartController('startAction')->startAction('http://example.com')->getStatusCode());
//    }


//    public function testRequestAsTeamMemberReturnsExistingTeam() {
//        $leader = $this->createAndActivateUser('leader@example.com', 'password');
//
//        $team = $this->getTeamService()->create(
//            'Foo',
//            $leader
//        );
//
//        $user = $this->createAndActivateUser('user@example.com', 'password');
//        $this->getUserService()->setUser($user);
//
//        $this->getTeamMemberService()->add($team, $user);
//
//        $methodName = $this->getActionNameFromRouter();
//
//        $response = $this->getCurrentController([
//            'name' => 'Foo'
//        ])->$methodName();
//
//        $this->assertEquals(302, $response->getStatusCode());
//        $this->assertEquals('/team/', $response->headers->get('location'));
//        $this->assertEquals('Foo', $response->headers->get('X-Team-Name'));
//    }
//
//
//    public function testRequestAsNonLeaderAndNonMemberCreatesNewTeam() {
//        $user = $this->createAndActivateUser('user@example.com', 'password');
//        $this->getUserService()->setUser($user);
//
//        $this->assertNull($this->getTeamService()->getForUser($user));
//
//        $methodName = $this->getActionNameFromRouter();
//
//        $response = $this->getCurrentController([
//            'name' => 'Foo'
//        ])->$methodName();
//
//        $this->assertEquals(302, $response->getStatusCode());
//        $this->assertEquals('/team/', $response->headers->get('location'));
//        $this->assertEquals('Foo', $response->headers->get('X-Team-Name'));
//
//        $this->assertNotNull($this->getTeamService()->getForUser($user));
//    }
//
//
//    public function testPublicUserCannotCreateTeam() {
//        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
//
//        $methodName = $this->getActionNameFromRouter();
//
//        $response = $this->getCurrentController([
//            'name' => 'Foo'
//        ])->$methodName();
//
//        $this->assertEquals(400, $response->getStatusCode());
//        $this->assertEquals(9, $response->headers->get('X-TeamCreate-Error-Code'));
//        $this->assertEquals('Special users cannot create teams', $response->headers->get('X-TeamCreate-Error-Message'));
//    }
//
//
//    public function testAdminUserCannotCreateTeam() {
//        $this->getUserService()->setUser($this->getUserService()->getAdminUser());
//
//        $methodName = $this->getActionNameFromRouter();
//
//        $response = $this->getCurrentController([
//            'name' => 'Foo'
//        ])->$methodName();
//
//        $this->assertEquals(400, $response->getStatusCode());
//        $this->assertEquals(9, $response->headers->get('X-TeamCreate-Error-Code'));
//        $this->assertEquals('Special users cannot create teams', $response->headers->get('X-TeamCreate-Error-Message'));
//    }
//
//
//    public function testUserInvitesDeletedOnTeamCreate() {
//        $leader = $this->createAndActivateUser('leader@example.com');
//        $user = $this->createAndActivateUser('user@example.com');
//
//        $this->getTeamService()->create('Foo1', $leader);
//
//        $this->getTeamInviteService()->get($leader, $user);
//
//        $this->assertTrue($this->getTeamInviteService()->hasAnyForUser($user));
//
//        $this->getUserService()->setUser($user);
//
//        $methodName = $this->getActionNameFromRouter();
//
//        $this->getCurrentController([
//            'name' => 'Foo2'
//        ])->$methodName();
//
//        $this->assertFalse($this->getTeamInviteService()->hasAnyForUser($user));
//
//    }
    
}