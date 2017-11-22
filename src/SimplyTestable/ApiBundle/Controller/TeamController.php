<?php

namespace SimplyTestable\ApiBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Exception\Services\Team\Exception as TeamServiceException;
use SimplyTestable\ApiBundle\Services\Team\InviteService as TeamInviteService;
use SimplyTestable\ApiBundle\Services\Team\MemberService as TeamMemberService;
use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use SimplyTestable\ApiBundle\Services\UserService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class TeamController
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var TeamService
     */
    private $teamService;

    /**
     * @var TeamMemberService
     */
    private $teamMemberService;

    /**
     * @var TeamInviteService
     */
    private $teamInviteService;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param RouterInterface $router
     * @param TeamService $teamService
     * @param TeamMemberService $teamMemberService
     * @param TeamInviteService $teamInviteService
     * @param UserService $userService
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        RouterInterface $router,
        TeamService $teamService,
        TeamMemberService $teamMemberService,
        TeamInviteService $teamInviteService,
        UserService $userService,
        EntityManagerInterface $entityManager
    ) {
        $this->router = $router;
        $this->teamService = $teamService;
        $this->teamMemberService = $teamMemberService;
        $this->teamInviteService = $teamInviteService;
        $this->userService = $userService;
        $this->entityManager = $entityManager;
    }

    /**
     * @param UserInterface|User $user
     *
     * @return JsonResponse
     */
    public function getAction(UserInterface $user)
    {
        $team = $this->teamService->getForUser($user);

        if (empty($team)) {
            throw new NotFoundHttpException();
        }

        $members = $this->teamMemberService->getMembers($team);

        $serializedMembers = [];

        foreach ($members as $member) {
            $serializedMembers[] = $member->getUser()->getUsername();
        }

        return new JsonResponse([
            'team' => $team,
            'members' => $serializedMembers
        ]);
    }

    /**
     * @param Request $request
     * @param UserInterface|User $user
     *
     * @return RedirectResponse|Response
     */
    public function createAction(Request $request, UserInterface $user)
    {
        if ($this->userService->isSpecialUser($user)) {
            return Response::create('', 400, [
                'X-TeamCreate-Error-Code' => 9,
                'X-TeamCreate-Error-Message' => 'Special users cannot create teams',
            ]);
        }

        $requestData = $request->request;
        $requestName = trim($requestData->get('name'));

        try {
            $this->teamService->create($requestName, $user);

            $invites = $this->teamInviteService->getForUser($user);
            foreach ($invites as $invite) {
                $this->entityManager->remove($invite);
                $this->entityManager->flush();
            }

        } catch (TeamServiceException $teamServiceException) {
            $isUserAlreadyLeadsTeamException = $teamServiceException->isUserAlreadyLeadsTeamException();
            $isUserAlreadyOnTeamException = $teamServiceException->isUserAlreadyOnTeamException();

            if ($isUserAlreadyLeadsTeamException || $isUserAlreadyOnTeamException) {
                return $this->createTeamGetRedirectResponse($user);
            }

            return Response::create('', 400, [
                'X-TeamCreate-Error-Code' => $teamServiceException->getCode(),
                'X-TeamCreate-Error-Message' => $teamServiceException->getMessage(),
            ]);
        }

        return $this->createTeamGetRedirectResponse($user);
    }

    /**
     * @param UserInterface|User $leader
     * @param string $member_email
     *
     * @return Response
     */
    public function removeAction(UserInterface $leader, $member_email)
    {
        if (!$this->userService->exists($member_email)) {
            return Response::create('', 400, [
                'X-TeamRemove-Error-Code' => 9,
                'X-TeamRemove-Error-Message' => 'Member is not a user',
            ]);
        }

        $member = $this->userService->findUserByEmail($member_email);

        try {
            $this->teamService->remove($leader, $member);
        } catch (TeamServiceException $teamServiceException) {
            return Response::create('', 400, [
                'X-TeamRemove-Error-Code' => $teamServiceException->getCode(),
                'X-TeamRemove-Error-Message' => $teamServiceException->getMessage(),
            ]);
        }

        return new Response();
    }

    /**
     * @param UserInterface|User $user
     *
     * @return Response
     */
    public function leaveAction(UserInterface $user)
    {
        if ($this->teamService->hasTeam($user)) {
            return Response::create('', 400, [
                'X-TeamLeave-Error-Code' => 9,
                'X-TeamLeave-Error-Message' => 'Leader cannot leave team',
            ]);
        }

        $this->teamMemberService->remove($user);

        return new Response();
    }

    /**
     * @param User $user
     *
     * @return RedirectResponse
     */
    private function createTeamGetRedirectResponse(User $user)
    {
        $url = $this->router->generate(
            'team_get',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $response = new RedirectResponse($url);

        $team = $this->teamService->getForUser($user);

        $response->headers->set('X-Team-Name', $team->getName());
        $response->headers->set('X-Team-ID', $team->getId());

        return $response;
    }
}
