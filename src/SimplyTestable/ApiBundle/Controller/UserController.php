<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Entity\User;

class UserController extends AbstractUserController
{       
    public function getAction() {        
        return $this->sendResponse($this->getUserSummary($this->getUser()));
    }
    
    
    private function getUserSummary(User $user) {        
        return array(
            'email' => $user->getEmailCanonical(),
            'plan' => $this->getPlanSummary($user)
        );
    }
    
    private function getPlanSummary(User $user) {
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($user);        
        $stripePlan = $this->getStripeService()->getPlan($userAccountPlan);
        
        $planSummary = array(
            'name' => $userAccountPlan->getPlan()->getName()
        );
        
        if (is_array($stripePlan)) {
            $planSummary['interval'] = $stripePlan['interval'];
            $planSummary['amount'] =  $stripePlan['amount'];
        }
        
        return $planSummary;       
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
}
