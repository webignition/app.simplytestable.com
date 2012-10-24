<?php

namespace SimplyTestable\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;

class UserPasswordResetController extends UserController
{
    
    public function __construct() {
        $this->setInputDefinitions(array(
            'resetPasswordAction' => new InputDefinition(array(
                new InputArgument('password', InputArgument::REQUIRED, 'Choice of new user password')
            ))
        ));
        
        $this->setRequestTypes(array(
            'resetPasswordAction' => HTTP_METH_POST
        ));        
    }    
    
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
    
    
    public function resetPasswordAction($token) {  
        $user = $this->getUserService()->findUserByConfirmationToken($token);        
        if (is_null($user)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(404);
        }
        
        if (!$user->isEnabled()) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(403);
        }

        $user->setPlainPassword($this->getArguments('resetPasswordAction')->get('password'));
        $user->setConfirmationToken(null);
        $user->setPasswordRequestedAt(null);
        
        $this->getUserService()->updateUser($user);
        
        return new \Symfony\Component\HttpFoundation\Response();        
    }

}
