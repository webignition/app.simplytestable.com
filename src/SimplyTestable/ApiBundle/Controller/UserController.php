<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;

class UserController extends AbstractUserController
{       
    public function getAction() {
        return $this->sendResponse($this->getUserSummary($this->getUser()));
    }


    public function authenticateAction() {
        return new Response('');
    }
    
    
    private function getUserSummary(User $user) {        
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($this->getUser());
        if (is_null($userAccountPlan)) {            
            $userAccountPlan = $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('basic'));
        }
        
        $userSummary = array(
            'email' => $user->getEmailCanonical(),
            'user_plan' => $userAccountPlan
        );

        if ($this->includeStripeCustomerInUserSummary($user, $userAccountPlan)) {
            $userSummary['stripe_customer'] = $this->getStripeService()->getCustomer($userAccountPlan)->__toArray();
        }

        $planConstraints = array();

        if ($userAccountPlan->getPlan()->hasConstraintNamed('credits_per_month')) {
            $this->getJobUserAccountPlanEnforcementService()->setUser($this->getUser());
            $planConstraints['credits'] = array(
                'limit' => $userAccountPlan->getPlan()->getConstraintNamed('credits_per_month')->getLimit(),
                'used' => $this->getJobUserAccountPlanEnforcementService()->getCreditsUsedThisMonth()
            );
        }
        
        if ($userAccountPlan->getPlan()->hasConstraintNamed('urls_per_job')) {
            $planConstraints['urls_per_job'] = $userAccountPlan->getPlan()->getConstraintNamed('urls_per_job')->getLimit();          
        }
        
        $userSummary['plan_constraints'] = $planConstraints;

        $userSummary['team_summary'] = [
            'in' => $this->getTeamService()->hasForUser($this->getUser()),
            'has_invite' => $this->getTeamInviteService()->hasAnyForUser($this->getUser())
        ];

        return $userSummary;
    }


    /**
     * @param User $user
     * @param UserAccountPlan $userAccountPlan
     * @return bool
     */
    private function includeStripeCustomerInUserSummary(User $user, UserAccountPlan $userAccountPlan) {
        return $userAccountPlan->hasStripeCustomer() &&  $user->getId() == $userAccountPlan->getUser()->getId();
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
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\JobUserAccountPlanEnforcementService
     */
    private function getJobUserAccountPlanEnforcementService() {
        return $this->get('simplytestable.services.jobuseraccountplanenforcementservice');
    }

    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\Team\Service
     */
    private function getTeamService() {
        return $this->get('simplytestable.services.teamservice');
    }


    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\Team\InviteService
     */
    private function getTeamInviteService() {
        return $this->get('simplytestable.services.teaminviteservice');
    }


    /**
     * @param $email_canonical
     * @return Response
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function hasInvitesAction($email_canonical) {
        if (!$this->getUserService()->exists($email_canonical)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(404);
        }

        if ($this->getTeamInviteService()->hasAnyForUser($this->getUserService()->findUserByEmail($email_canonical))) {
            return new \Symfony\Component\HttpFoundation\Response('', 200);
        }

        throw new \Symfony\Component\HttpKernel\Exception\HttpException(404);
    }
}
