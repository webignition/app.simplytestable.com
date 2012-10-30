<?php

namespace SimplyTestable\ApiBundle\Controller;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;

class UserCreationController extends UserController
{
    
    public function __construct() {
        $this->setInputDefinitions(array(
            'createAction' => new InputDefinition(array(
                new InputArgument('email', InputArgument::REQUIRED, 'User email address')
            ))
        ));
        
        $this->setRequestTypes(array(
            'createAction' => HTTP_METH_POST
        ));        
    }
    
    public function createAction()            
    {        
        $email = $this->getArguments('createAction')->get('email');
        $password = md5($email . microtime(true) . $this->container->getParameter('secret'));
        
        if ($this->getUserService()->exists($email)) {
            $user = $this->getUserService()->findUserByEmail($email);
            
            if ($user->isEnabled()) {
                return $this->redirect($this->generateUrl('user', array(
                    'email_canonical' => $email
                ), true));                
            }           
        }
        
        $this->getUserService()->create($email, $password);
        
        return new \Symfony\Component\HttpFoundation\Response();
    }
    
    
    public function activateAction($token) {
        $user = $this->getUserService()->findUserByConfirmationToken($token);
        if (is_null($user)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(400);
        }
        
        $this->getUserManipulator()->activate($user->getUsername());
        
        return new \Symfony\Component\HttpFoundation\Response();
    } 

}
