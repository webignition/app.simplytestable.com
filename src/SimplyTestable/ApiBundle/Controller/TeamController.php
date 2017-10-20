<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Exception\Services\Team\Exception as TeamServiceException;
use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TeamController extends ApiController
{
    /**
     * @return Response
     */
    public function getAction()
    {
        $teamService = $this->container->get('simplytestable.services.teamservice');
        $teamMemberService = $this->container->get('simplytestable.services.teammemberservice');

        $user = $this->getUser() ;
        $team = $teamService->getForUser($user);

        if (empty($team)) {
            throw new NotFoundHttpException();
        }

        $members = $teamMemberService->getMembers($team);

        $serializedMembers = [];

        foreach ($members as $member) {
            $serializedMembers[] = $member->getUser()->getUsername();
        }

        return $this->sendResponse([
            'team' => $team,
            'members' => $serializedMembers
        ]);
    }

    /**
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function createAction(Request $request)
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $teamService = $this->container->get('simplytestable.services.teamservice');
        $teamInviteService = $this->container->get('simplytestable.services.teaminviteservice');

        if ($userService->isSpecialUser($this->getUser())) {
            return $this->sendFailureResponse([
                'X-TeamCreate-Error-Code' => 9,
                'X-TeamCreate-Error-Message' => 'Special users cannot create teams',
            ]);
        }

        $user = $this->getUser();

        $requestData = $request->request;
        $requestName = trim($requestData->get('name'));

        try {
            $teamService->create($requestName, $user);

            $invites = $teamInviteService->getForUser($this->getUser());
            foreach ($invites as $invite) {
                $teamInviteService->remove($invite);
            }

        } catch (TeamServiceException $teamServiceException) {
            $isUserAlreadyLeadsTeamException = $teamServiceException->isUserAlreadyLeadsTeamException();
            $isUserAlreadyOnTeamException = $teamServiceException->isUserAlreadyOnTeamException();

            if ($isUserAlreadyLeadsTeamException || $isUserAlreadyOnTeamException) {
                return $this->createTeamGetRedirectResponse($teamService);
            }

            return $this->sendFailureResponse([
                'X-TeamCreate-Error-Code' => $teamServiceException->getCode(),
                'X-TeamCreate-Error-Message' => $teamServiceException->getMessage(),
            ]);
        }

        return $this->createTeamGetRedirectResponse($teamService);
    }

    /**
     * @param string $member_email
     *
     * @return Response
     */
    public function removeAction($member_email)
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $teamService = $this->container->get('simplytestable.services.teamservice');

        if (!$userService->exists($member_email)) {
            return $this->sendFailureResponse([
                'X-TeamRemove-Error-Code' => 9,
                'X-TeamRemove-Error-Message' => 'Member is not a user',
            ]);
        }

        $member = $userService->findUserByEmail($member_email);

        try {
            $teamService->remove($this->getUser(), $member);

            return $this->sendResponse();
        } catch (TeamServiceException $teamServiceException) {
            return $this->sendFailureResponse([
                'X-TeamRemove-Error-Code' => $teamServiceException->getCode(),
                'X-TeamRemove-Error-Message' => $teamServiceException->getMessage(),
            ]);
        }
    }

    /**
     * @return Response
     */
    public function leaveAction()
    {
        $teamService = $this->container->get('simplytestable.services.teamservice');
        $teamMemberService = $this->container->get('simplytestable.services.teammemberservice');

        if ($teamService->hasTeam($this->getUser())) {
            return $this->sendFailureResponse([
                'X-TeamLeave-Error-Code' => 9,
                'X-TeamLeave-Error-Message' => 'Leader cannot leave team',
            ]);
        }

        $teamMemberService->remove($this->getUser());

        return $this->sendResponse();
    }

    /**
     * @param TeamService $teamService
     *
     * @return RedirectResponse
     */
    private function createTeamGetRedirectResponse(TeamService $teamService)
    {
        $response = $this->redirect($this->generateUrl('team_get'));

        $team = $teamService->getForUser($this->getUser());

        $response->headers->set('X-Team-Name', $team->getName());
        $response->headers->set('X-Team-ID', $team->getId());

        return $response;
    }
}
