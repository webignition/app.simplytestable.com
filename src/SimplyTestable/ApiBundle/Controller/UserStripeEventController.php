<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Services\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class UserStripeEventController extends Controller
{
    /**
     * @param string $email_canonical
     * @param string $type
     *
     * @return JsonResponse|Response
     */
    public function listAction($email_canonical, $type)
    {
        $userService = $this->container->get(UserService::class);
        $stripeEventService = $this->container->get('simplytestable.services.stripeeventservice');

        if ($userService->isPublicUser($this->getUser())) {
            return Response::create('', 400);
        }

        if ($email_canonical !== $this->getUser()->getEmail()) {
            return Response::create('', 400);
        }

        $events = $stripeEventService->getForUserAndType($this->getUser(), $type);

        return new JsonResponse($events);
    }
}
