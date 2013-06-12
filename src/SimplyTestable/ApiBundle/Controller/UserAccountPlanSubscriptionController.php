<?php

namespace SimplyTestable\ApiBundle\Controller;

class UserAccountPlanSubscriptionController extends AbstractUserController
{
    
    public function __construct() {    
        $this->setRequestTypes(array(
            'subscribeAction' => \Guzzle\Http\Message\Request::POST
        ));        
    }
    
    public function subscribeAction($email_canonical, $plan_name)            
    {        
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }
        
        if ($this->getUserService()->isPublicUser($this->getUser())) {
            return $this->sendFailureResponse();
        }
        
        if ($email_canonical !== $this->getUser()->getEmail()) {
            return $this->sendFailureResponse();
        }
        
        if (!$this->getAccountPlanService()->has($plan_name)) {
            return $this->sendFailureResponse();
        }
        
        try {
            $this->getUserAccountPlanService()->subscribe($this->getUser(), $this->getAccountPlanService()->find($plan_name));
        } catch (\Stripe_AuthenticationError $stripeAuthenticationError) {
            return $this->sendForbiddenResponse();
        }        
        
        return $this->sendSuccessResponse();
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
