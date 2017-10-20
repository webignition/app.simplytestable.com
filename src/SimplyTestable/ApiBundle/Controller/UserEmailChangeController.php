<?php

namespace SimplyTestable\ApiBundle\Controller;

use Egulias\EmailValidator\EmailValidator;
use Symfony\Component\HttpFoundation\Response;

class UserEmailChangeController extends ApiController
{
    public function createAction($email_canonical, $new_email) {
        $userService = $this->container->get('simplytestable.services.userservice');

        $email_canonical = $this->getUserEmailChangeRequestService()->canonicalizeEmail($email_canonical);
        $new_email = $this->getUserEmailChangeRequestService()->canonicalizeEmail($new_email);

        $user = $this->getUser();

        if (is_null($user)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(404);
        }

        if (!$user->isEnabled()) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(404);
        }

        if ($user->getEmailCanonical() !== $email_canonical) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(404);
        }

        if ($this->getUserEmailChangeRequestService()->hasForUser($user)) {
            if ($this->getUserEmailChangeRequestService()->findByUser($user)->getNewEmail() === $new_email) {
                return new Response();
            }

            throw new \Symfony\Component\HttpKernel\Exception\HttpException(409);
        }

        if (!$this->isEmailValid($new_email)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(400);
        }

        if ($userService->exists($new_email)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(409);
        }

        if ($this->getUserEmailChangeRequestService()->hasForNewEmail($new_email)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(409);
        }

        $this->getUserEmailChangeRequestService()->create($user, $new_email);

        return new Response();
    }


    public function getAction($email_canonical) {
        $userService = $this->container->get('simplytestable.services.userservice');

        $user = $userService->findUserByEmail($email_canonical);
        if (is_null($user)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(404);
        }

        $emailChangeRequest = $this->getUserEmailChangeRequestService()->findByUser($user);
        if (is_null($emailChangeRequest)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(404);
        }

        return $this->sendResponse($emailChangeRequest);
    }


    public function cancelAction($email_canonical) {
        $email_canonical = $this->getUserEmailChangeRequestService()->canonicalizeEmail($email_canonical);

        $user = $this->getUser();

        if (is_null($user)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(404);
        }

        if ($user->getEmailCanonical() !== $email_canonical) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(404);
        }

        $this->getUserEmailChangeRequestService()->removeForUser($user);

        return new Response();
    }


    public function confirmAction($email_canonical, $token) {
        $userService = $this->container->get('simplytestable.services.userservice');

        $email_canonical = $this->getUserEmailChangeRequestService()->canonicalizeEmail($email_canonical);

        $user = $this->getUser();

        if (is_null($user)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(404);
        }

        if ($user->getEmailCanonical() !== $email_canonical) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(404);
        }

        $emailChangeRequest = $this->getUserEmailChangeRequestService()->findByUser($user);
        if (is_null($emailChangeRequest)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(404);
        }

        if ($token !== $emailChangeRequest->getToken()) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(400);
        }

        if ($userService->exists($emailChangeRequest->getNewEmail())) {
            $this->getUserEmailChangeRequestService()->removeForUser($user);
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(409);
        }

        $user->setEmail($emailChangeRequest->getNewEmail());
        $user->setEmailCanonical($emailChangeRequest->getNewEmail());
        $user->setUsername($emailChangeRequest->getNewEmail());
        $user->setUsernameCanonical($emailChangeRequest->getNewEmail());

        $userService->updateUser($user);

        $this->getUserEmailChangeRequestService()->removeForUser($user);

        return new Response();
    }



    /**
     *
     * @param string $email
     * @return boolean
     */
    private function isEmailValid($email) {
        $validator = new EmailValidator;
        return $validator->isValid($email);
    }


    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\UserEmailChangeRequestService
     */
    protected function getUserEmailChangeRequestService() {
        return $this->get('simplytestable.services.useremailchangerequestservice');
    }



}
