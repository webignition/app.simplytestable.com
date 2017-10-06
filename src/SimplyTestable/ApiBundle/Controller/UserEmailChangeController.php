<?php

namespace SimplyTestable\ApiBundle\Controller;

use Egulias\EmailValidator\EmailValidator;

class UserEmailChangeController extends AbstractUserController
{
    public function createAction($email_canonical, $new_email) {
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
                return $this->sendResponse();
            }

            throw new \Symfony\Component\HttpKernel\Exception\HttpException(409);
        }

        if (!$this->isEmailValid($new_email)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(400);
        }

        if ($this->getUserService()->exists($new_email)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(409);
        }

        if ($this->getUserEmailChangeRequestService()->hasForNewEmail($new_email)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(409);
        }

        $this->getUserEmailChangeRequestService()->create($user, $new_email);

        return $this->sendResponse();
    }


    public function getAction($email_canonical) {
        $user = $this->getUserService()->findUserByEmail($email_canonical);
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

        return $this->sendResponse();
    }


    public function confirmAction($email_canonical, $token) {
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

        if ($this->getUserService()->exists($emailChangeRequest->getNewEmail())) {
            $this->getUserEmailChangeRequestService()->removeForUser($user);
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(409);
        }

        $user->setEmail($emailChangeRequest->getNewEmail());
        $user->setEmailCanonical($emailChangeRequest->getNewEmail());
        $user->setUsername($emailChangeRequest->getNewEmail());
        $user->setUsernameCanonical($emailChangeRequest->getNewEmail());

        $this->getUserService()->updateUser($user);

        $this->getUserEmailChangeRequestService()->removeForUser($user);

        return $this->sendResponse();
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
