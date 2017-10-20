<?php

namespace SimplyTestable\ApiBundle\Controller;

class UserStripeEventController extends ApiController
{
    public function listAction($email_canonical, $type = null)
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $stripeEventService = $this->container->get('simplytestable.services.stripeeventservice');

        if ($userService->isPublicUser($this->getUser())) {
            return $this->sendFailureResponse();
        }

        if ($email_canonical !== $this->getUser()->getEmail()) {
            return $this->sendFailureResponse();
        }

        $events = $stripeEventService->getForUserAndType($this->getUser(), $type);

        return $this->sendResponse($events);
    }
}
