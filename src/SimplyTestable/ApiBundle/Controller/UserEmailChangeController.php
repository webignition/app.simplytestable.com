<?php

namespace SimplyTestable\ApiBundle\Controller;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use SimplyTestable\ApiBundle\Entity\User;

class UserEmailChangeController extends AbstractUserController
{
    
//    public function __construct() {
//        $this->setInputDefinitions(array(
//            'createAction' => new InputDefinition(array(
//                new InputArgument('email', InputArgument::REQUIRED, 'User email address'),
//                new InputArgument('password', InputArgument::REQUIRED, 'User password')
//            ))
//        ));
//        
//        $this->setRequestTypes(array(
//            'createAction' => \Guzzle\Http\Message\Request::POST
//        ));        
//    }
    
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
        if (strpos($email, '@') <= 0) {
            return false;
        }
        
        try {
            $message = \Swift_Message::newInstance();
            $message->setTo($email);            
            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\UserEmailChangeRequestService 
     */
    protected function getUserEmailChangeRequestService() {
        return $this->get('simplytestable.services.useremailchangerequestservice');
    }    
    
   

}
