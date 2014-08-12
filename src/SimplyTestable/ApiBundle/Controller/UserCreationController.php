<?php

namespace SimplyTestable\ApiBundle\Controller;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan as AccountPlan;

class UserCreationController extends AbstractUserController
{
    const DEFAULT_ACCOUNT_PLAN_NAME = 'basic';
    
    public function __construct() {
        $this->setInputDefinitions(array(
            'createAction' => new InputDefinition(array(
                new InputArgument('email', InputArgument::REQUIRED, 'User email address'),
                new InputArgument('password', InputArgument::REQUIRED, 'User password'),
                new InputArgument('plan', InputArgument::OPTIONAL, 'Plan for user')
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
        
        $email = rawurldecode($this->getArguments('createAction')->get('email'));
        $password = rawurldecode($this->getArguments('createAction')->get('password'));

        if ($this->getUserService()->exists($email)) {
            $user = $this->getUserService()->findUserByEmail($email);
            
            if ($user->isEnabled()) {
                return $this->redirect($this->generateUrl('user_get', array(
                    'email_canonical' => $email
                ), true));                
            }

            $user->setPlainPassword($password);
            $this->getUserService()->updatePassword($user);
        } else {
            $user = $this->getUserService()->create($email, $password);
        }
        
        if ($user instanceof User) {
            $coupon = trim(rawurldecode($this->getArguments('createAction')->get('coupon')));
            $this->getUserAccountPlanService()->subscribe($user, $this->getNewUserPlan(), $coupon);
        }
        
        return new \Symfony\Component\HttpFoundation\Response();
    }
    
    
    /**
     * 
     * @return AccountPlan
     */
    private function getNewUserPlan() {
        $planName = $this->getArguments('createAction')->get('plan');
        if (is_null($planName) || !$this->getAccountPlanService()->has($planName)) {
            $planName = self::DEFAULT_ACCOUNT_PLAN_NAME;
        }
        
        return $this->getAccountPlanService()->find($planName);    
    }
    
    
    public function activateAction($token = null) {
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }
        
        $token = trim($token);        
        if ($token == '') {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(400);
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
