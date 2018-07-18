<?php

namespace Tests\AppBundle\Unit\Controller\Team;

use Doctrine\ORM\EntityManagerInterface;
use Mockery\Mock;
use AppBundle\Controller\TeamController;
use AppBundle\Services\UserService;
use Symfony\Component\Routing\RouterInterface;
use Tests\AppBundle\Factory\MockFactory;
use AppBundle\Services\Team\InviteService as TeamInviteService;
use AppBundle\Services\Team\MemberService as TeamMemberService;
use AppBundle\Services\Team\Service as TeamService;

abstract class AbstractTeamControllerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param array $services
     *
     * @return TeamController
     */
    protected function createTeamController($services = [])
    {
        if (!isset($services[RouterInterface::class])) {
            /* @var Mock|RouterInterface $router */
            $router = \Mockery::mock(RouterInterface::class);

            $services[RouterInterface::class] = $router;
        }

        if (!isset($services[TeamService::class])) {
            $services[TeamService::class] = MockFactory::createTeamService();
        }

        if (!isset($services[TeamInviteService::class])) {
            $services[TeamInviteService::class] = MockFactory::createTeamInviteService();
        }

        if (!isset($services[TeamMemberService::class])) {
            $services[TeamMemberService::class] = MockFactory::createTeamMemberService();
        }

        if (!isset($services[UserService::class])) {
            $services[UserService::class] = MockFactory::createUserService();
        }

        if (!isset($services[EntityManagerInterface::class])) {
            $services[EntityManagerInterface::class] = MockFactory::createEntityManager();
        }

        $teamController = new TeamController(
            $services[RouterInterface::class],
            $services[TeamService::class],
            $services[TeamMemberService::class],
            $services[TeamInviteService::class],
            $services[UserService::class],
            $services[EntityManagerInterface::class]
        );

        return $teamController;
    }
}
