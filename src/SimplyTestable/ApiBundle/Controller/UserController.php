<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Entity\User;

class UserController extends AbstractUserController
{       
    public function getAction() {        
        return $this->sendResponse($this->getUserSummary($this->getUser()));
    }
    
    
    public function getCardSummaryAction() {
        $card = array();        
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($this->getUser());

        if ($userAccountPlan->hasStripeCustomer()) {
            $customer = $this->getStripeService()->getCustomer($userAccountPlan);
            $card = $customer['active_card'];
        }        
        
        return $this->sendResponse($card);
    }
    
    
    public function getPlanAction() {
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($this->getUser());

        $planDetails = array();
        $planDetails['name'] = $userAccountPlan->getPlan()->getName();
        
        if ($userAccountPlan->hasStripeCustomer()) {
            $customer = $this->getStripeService()->getCustomer($userAccountPlan);

            $planProperties = array(
                'interval',
                'amount',
                'trial_period_days'
            );
            
            $subscriptionProperties = array(
                'status',
                'current_period_end',
                'trial_end'
            );            
            
            $planDetails['summary'] = array();
            
            foreach ($planProperties as $planProperty) {
                $planDetails['summary'][$planProperty] = $customer['subscription']['plan'][$planProperty];
            }
            
            foreach ($subscriptionProperties as $subscriptionProperty) {
                $planDetails['summary'][$subscriptionProperty] = $customer['subscription'][$subscriptionProperty];
            }            
        }
        
        
        if ($userAccountPlan->getPlan()->hasConstraintNamed('credits_per_month')) {
            $this->getJobUserAccountPlanEnforcementService()->setUser($this->getUser());
            $planDetails['credits'] = array(
                'limit' => $userAccountPlan->getPlan()->getConstraintNamed('credits_per_month')->getLimit(),
                'used' => $this->getJobUserAccountPlanEnforcementService()->getCreditsUsedThisMonth()
            );            
        }
        
        if ($userAccountPlan->getPlan()->hasConstraintNamed('urls_per_job')) {
            $planDetails['urls_per_job'] = $userAccountPlan->getPlan()->getConstraintNamed('urls_per_job')->getLimit();          
        }
        
        return $this->sendResponse($planDetails);
    }
    
    
    private function getUserSummary(User $user) {        
        return array(
            'email' => $user->getEmailCanonical()
        );
    }
    
    
    /**
     * 
     * @param string $email_canonical
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function getTokenAction($email_canonical)            
    {        
        $user = $this->getUserService()->findUserByEmail($email_canonical);
        if (is_null($user)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(404);
        }
        
        $token = $this->getUserService()->getConfirmationToken($user);
        
        return $this->sendResponse($token);
    }     
    

    /**
     * 
     * @param string $email_canonical
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function isEnabledAction($email_canonical) {
        $user = $this->getUserService()->findUserByEmail($email_canonical);
        
        if (is_null($user)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(404);
        }
        
        if ($user->isEnabled() === false) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(404);
        }
        
        return new \Symfony\Component\HttpFoundation\Response('', 200);
    }    
    
    
    /**
     * 
     * @param string $email_canonical
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function existsAction($email_canonical) {
        if ($this->getUserService()->exists($email_canonical)) {
            return new \Symfony\Component\HttpFoundation\Response('', 200);
        }
        
        throw new \Symfony\Component\HttpKernel\Exception\HttpException(404);
    }
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\StripeService
     */
    private function getStripeService() {
        return $this->get('simplytestable.services.stripeservice');
    }
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\UserAccountPlanService 
     */
    private function getUserAccountPlanService() {
        return $this->get('simplytestable.services.useraccountplanservice');
    }     
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\JobUserAccountPlanEnforcementService
     */
    private function getJobUserAccountPlanEnforcementService() {
        return $this->get('simplytestable.services.jobuseraccountplanenforcementservice');
    }       
}
