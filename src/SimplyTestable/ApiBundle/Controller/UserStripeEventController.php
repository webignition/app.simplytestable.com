<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\StripeEventService;
use SimplyTestable\ApiBundle\Services\UserService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserStripeEventController
{
    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var StripeEventService
     */
    private $stripeEventService;

    /**
     * @param UserService $userService
     * @param StripeEventService $stripeEventService
     */
    public function __construct(
        UserService $userService,
        StripeEventService $stripeEventService
    ) {
        $this->userService = $userService;
        $this->stripeEventService = $stripeEventService;
    }

    /**
     * @param UserInterface|User $user
     * @param string $email_canonical
     * @param string $type
     *
     * @return JsonResponse|Response
     */
    public function listAction(UserInterface $user, $email_canonical, $type)
    {
        if ($this->userService->isPublicUser($user)) {
            throw new BadRequestHttpException();
        }

        if ($email_canonical !== $user->getEmail()) {
            throw new BadRequestHttpException();
        }

        $events = $this->stripeEventService->getForUserAndType($user, $type);

        return new JsonResponse($events);
    }
}
