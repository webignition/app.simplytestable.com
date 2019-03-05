<?php

namespace App\Controller;

use App\Repository\TeamRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Util\UserManipulator;
use App\Entity\User;
use App\Exception\Services\TeamInvite\Exception as TeamInviteServiceException;
use App\Entity\Team\Team;
use App\Services\AccountPlanService;
use App\Services\Job\ConfigurationService;
use App\Services\ScheduledJob\Service as ScheduledJobService;
use App\Services\Team\InviteService as TeamInviteService;
use App\Services\Team\MemberService as TeamMemberService;
use App\Services\Team\Service as TeamService;
use App\Services\UserAccountPlanService;
use App\Services\UserService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\User\UserInterface;

class TeamInviteController
{
    const DEFAULT_ACCOUNT_PLAN_NAME = 'basic';

    private $userService;
    private $accountPlanService;
    private $userAccountPlanService;
    private $teamService;
    private $teamInviteService;
    private $teamMemberService;
    private $entityManager;
    private $scheduledJobService;
    private $jobConfigurationService;
    private $userManipulator;
    private $teamRepository;

    public function __construct(
        UserService $userService,
        AccountPlanService $accountPlanService,
        UserAccountPlanService $userAccountPlanService,
        TeamService $teamService,
        TeamInviteService $teamInviteService,
        TeamMemberService $teamMemberService,
        EntityManagerInterface $entityManager,
        ScheduledJobService $scheduledJobService,
        ConfigurationService $jobConfigurationService,
        UserManipulator $userManipulator,
        TeamRepository $teamRepository
    ) {
        $this->userService = $userService;
        $this->accountPlanService = $accountPlanService;
        $this->userAccountPlanService = $userAccountPlanService;
        $this->teamService = $teamService;
        $this->teamInviteService = $teamInviteService;
        $this->teamMemberService = $teamMemberService;
        $this->entityManager = $entityManager;
        $this->scheduledJobService = $scheduledJobService;
        $this->jobConfigurationService = $jobConfigurationService;
        $this->userManipulator = $userManipulator;
        $this->teamRepository = $teamRepository;
    }

    /**
     * @param UserInterface|User $inviter
     * @param Request $request
     * @param string $invitee_email
     *
     * @return JsonResponse|Response
     */
    public function getAction(UserInterface $inviter, Request $request, $invitee_email)
    {
        if (!$this->teamService->hasTeam($inviter)) {
            return Response::create('', 400, [
                'X-TeamInviteGet-Error-Code' => TeamInviteServiceException::INVITER_IS_NOT_A_LEADER,
                'X-TeamInviteGet-Error-Message' => 'Inviter is not a team leader',
            ]);
        }

        if (!$this->userService->exists($invitee_email)) {
            $user = $this->userService->create($invitee_email, md5(rand()));

            $requestData = $request->query;

            $planName = rawurldecode(trim($requestData->get('plan')));
            $plan = $this->accountPlanService->get($planName);

            if (empty($plan)) {
                $plan = $this->accountPlanService->get(self::DEFAULT_ACCOUNT_PLAN_NAME);
            }

            $this->userAccountPlanService->subscribe($user, $plan);
        }

        $invitee = $this->userService->findUserByEmail($invitee_email);

        if ($this->userService->isSpecialUser($invitee)) {
            return Response::create('', 400, [
                'X-TeamInviteGet-Error-Code' => 10,
                'X-TeamInviteGet-Error-Message' => 'Special users cannot be invited',
            ]);
        }

        if ($this->userAccountPlanService->getForUser($invitee)->getPlan()->getIsPremium()) {
            return Response::create('', 400, [
                'X-TeamInviteGet-Error-Code' => 11,
                'X-TeamInviteGet-Error-Message' => 'Invitee has a premium plan',
            ]);
        }

        try {
            return new JsonResponse($this->teamInviteService->get($inviter, $invitee));
        } catch (TeamInviteServiceException $teamInviteServiceException) {
            return Response::create('', 400, [
                'X-TeamInviteGet-Error-Code' => $teamInviteServiceException->getCode(),
                'X-TeamInviteGet-Error-Message' => $teamInviteServiceException->getMessage(),
            ]);
        }
    }

    /**
     * @param Request $request
     * @param UserInterface|User $user
     *
     * @return Response
     */
    public function acceptAction(Request $request, UserInterface $user)
    {
        $requestData = $request->request;
        $requestTeam = $requestData->get('team');

        /* @var $team Team */
        $team = $this->teamRepository->findOneBy([
            'name' => $requestTeam,
        ]);

        if (is_null($team)) {
            return Response::create('', 400, [
                'X-TeamInviteAccept-Error-Code' => 1,
                'X-TeamInviteAccept-Error-Message' => 'Invalid team',
            ]);
        }

        $invite = $this->teamInviteService->getForTeamAndUser($team, $user);

        if (empty($invite)) {
            return Response::create('', 400, [
                'X-TeamInviteAccept-Error-Code' => 2,
                'X-TeamInviteAccept-Error-Message' => 'User has not been invited to join this team',
            ]);
        }

        if ($this->userAccountPlanService->getForUser($user)->getPlan()->getIsPremium()) {
            return new Response();
        }

        $this->scheduledJobService->removeAll();
        $this->jobConfigurationService->removeAll();

        $this->teamMemberService->add($invite->getTeam(), $invite->getUser());

        $invites = $this->teamInviteService->getForUser($user);

        foreach ($invites as $invite) {
            $this->entityManager->remove($invite);
            $this->entityManager->flush();
        }

        return new Response();
    }

    /**
     * @param UserInterface|User $user
     *
     * @return JsonResponse|Response
     */
    public function listAction(UserInterface $user)
    {
        if (!$this->teamService->hasTeam($user)) {
            return Response::create('', 400, [
                'X-TeamInviteList-Error-Code' => 1,
                'X-TeamInviteList-Error-Message' => 'User is not a team leader',
            ]);
        }

        $allInvites = $this->teamInviteService->getForTeam($this->teamService->getForUser($user));
        $invites = [];

        foreach ($allInvites as $invite) {
            $userAccountPlan = $this->userAccountPlanService->getForUser($invite->getUser());

            if (!$userAccountPlan->getPlan()->getIsPremium()) {
                $invites[] = $invite;
            }
        }

        return new JsonResponse($invites);
    }

    /**
     * @param UserInterface|User $user
     *
     * @return Response
     */
    public function userListAction(UserInterface $user)
    {
        if ($this->userAccountPlanService->getForUser($user)->getPlan()->getIsPremium()) {
            return new JsonResponse([]);
        }

        return new JsonResponse($this->teamInviteService->getForUser($user));
    }

    /**
     * @param UserInterface|User $leader
     * @param string $invitee_email
     *
     * @return Response
     */
    public function removeAction(UserInterface $leader, $invitee_email)
    {
        if (!$this->teamService->hasTeam($leader)) {
            return Response::create('', 400, [
                'X-TeamInviteRemove-Error-Code' => 1,
                'X-TeamInviteRemove-Error-Message' => 'User is not a team leader',
            ]);
        }

        if (!$this->userService->exists($invitee_email)) {
            return Response::create('', 400, [
                'X-TeamInviteRemove-Error-Code' => 2,
                'X-TeamInviteRemove-Error-Message' => 'Invitee is not a user',
            ]);
        }

        $team = $this->teamService->getForUser($leader);
        $invitee = $this->userService->findUserByEmail($invitee_email);
        $invite = $this->teamInviteService->getForTeamAndUser($team, $invitee);

        if (empty($invite)) {
            return Response::create('', 400, [
                'X-TeamInviteRemove-Error-Code' => 3,
                'X-TeamInviteRemove-Error-Message' => 'Invitee does not have an invite for this team',
            ]);
        }

        $this->entityManager->remove($invite);
        $this->entityManager->flush();

        return new Response();
    }

    /**
     * @param UserInterface|User $user
     * @param Request $request
     *
     * @return Response
     */
    public function declineAction(UserInterface $user, Request $request)
    {
        $requestData = $request->request;
        $requestTeam = $requestData->get('team');

        $team = $this->teamRepository->findOneBy([
            'name' => $requestTeam,
        ]);

        if ($team instanceof Team) {
            $invite = $this->teamInviteService->getForTeamAndUser($team, $user);

            if (!empty($invite)) {
                $this->entityManager->remove($invite);
                $this->entityManager->flush();
            }
        }

        return new Response();
    }

    /**
     * @param $token
     *
     * @return Response
     */
    public function getByTokenAction($token)
    {
        $invite = $this->teamInviteService->getForToken($token);

        if (empty($invite)) {
            throw new NotFoundHttpException();
        }

        return new JsonResponse($invite);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function activateAndAcceptAction(Request $request)
    {
        $requestData = $request->request;
        $token = trim($requestData->get('token'));

        $invite = $this->teamInviteService->getForToken($token);

        if (empty($invite)) {
            return Response::create('', 400, [
                'X-TeamInviteActivateAndAccept-Error-Code' => 1,
                'X-TeamInviteActivateAndAccept-Error-Message' => 'No invite for token',
            ]);
        }

        $invitee = $invite->getUser();
        $team = $invite->getTeam();

        $this->userManipulator->activate($invitee->getUsername());

        $password = rawurldecode(trim($requestData->get('password')));

        $invitee->setPlainPassword($password);
        $this->userService->updateUser($invitee);

        $this->teamMemberService->add($team, $invitee);

        $invites = $this->teamInviteService->getForUser($invitee);

        foreach ($invites as $invite) {
            $this->entityManager->remove($invite);
            $this->entityManager->flush();
        }

        return new Response();
    }
}
