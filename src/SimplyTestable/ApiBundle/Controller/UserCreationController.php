<?php

namespace SimplyTestable\ApiBundle\Controller;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use SimplyTestable\ApiBundle\Entity\User;

class UserCreationController extends AbstractUserController
{
    const DEFAULT_ACCOUNT_PLAN_NAME = 'basic';
    
    public function __construct() {
        $this->setInputDefinitions(array(
            'createAction' => new InputDefinition(array(
                new InputArgument('email', InputArgument::REQUIRED, 'User email address'),
                new InputArgument('password', InputArgument::REQUIRED, 'User password')
            ))
        ));
        
        $this->setRequestTypes(array(
            'createAction' => \Guzzle\Http\Message\Request::POST
        ));        
    }
    
    public function createAction()            
    {        
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }          
        
        $email = $this->getArguments('createAction')->get('email');
        $password = $this->getArguments('createAction')->get('password');        
        
        if ($this->getUserService()->exists($email)) {
            $user = $this->getUserService()->findUserByEmail($email);
            
            if ($user->isEnabled()) {
                return $this->redirect($this->generateUrl('user', array(
                    'email_canonical' => $email
                ), true));                
            }           
        }
        
        $user = $this->getUserService()->create($email, $password);
        
        if ($user instanceof User) {
            $plan = $this->getAccountPlanService()->find(self::DEFAULT_ACCOUNT_PLAN_NAME);        
            $this->getUserAccountPlanService()->subscribe($user, $plan);            
        }
        
        return new \Symfony\Component\HttpFoundation\Response();
    }
    
    
    public function activateAction($token) {
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }          
        
        $user = $this->getUserService()->findUserByConfirmationToken($token);
        if (is_null($user)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(400);
        }
        
        $this->getUserManipulator()->activate($user->getUsername());
        
        return new \Symfony\Component\HttpFoundation\Response();
    } 
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\AccountPlanService 
     */
    private function getAccountPlanService() {
        return $this->get('simplytestable.services.accountplanservice');
    }       
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\UserAccountPlanService 
     */
    private function getUserAccountPlanService() {
        return $this->get('simplytestable.services.useraccountplanservice');
    }    

}
