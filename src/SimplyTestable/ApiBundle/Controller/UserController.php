<?php

namespace SimplyTestable\ApiBundle\Controller;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use SimplyTestable\ApiBundle\Entity\User;

class UserController extends ApiController
{
    
    public function __construct() {
        $this->setInputDefinitions(array(
            'createAction' => new InputDefinition(array(
                new InputArgument('email', InputArgument::REQUIRED, 'User email address'),
                new InputArgument('password', InputArgument::REQUIRED, 'User password')
            ))
        ));
        
        $this->setRequestTypes(array(
            'createAction' => HTTP_METH_POST
        ));        
    }
    
    
    public function getAction() {
    }
    
    public function createAction()
    {        
        $email = $this->getArguments('createAction')->get('email');
        $password  = $this->getArguments('createAction')->get('password');
        
        $user = $this->getUserService()->create($email, $password);
        
        if (!$user->hasActivationToken()) {            
            $user->setConfirmationToken($this->getTokenGenerator()->generateToken());
        }
        
        return new \Symfony\Component\HttpFoundation\Response();
    }

    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\UserService 
     */
    private function getUserService() {
        return $this->get('simplytestable.services.userservice');
    }
    
    
    /**
     * 
     * @return \FOS\UserBundle\Util\UserManipulator 
     */
    private function getUserManipulator() {        
        return $this->get('fos_user.util.user_manipulator');
    }  
}
