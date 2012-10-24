<?php

namespace SimplyTestable\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Response;

class UserPasswordResetController extends UserController
{
    
    public function getTokenAction($email_canonical)            
    {        
        $user = $this->getUserService()->findUserByEmail($email_canonical);
        if (is_null($user)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(404);
        }
        
        if (!$user->isEnabled()) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(403);
        }
        
        $token = $this->getUserService()->getConfirmationToken($user);
        
        return $this->sendResponse($token);
    }

}
