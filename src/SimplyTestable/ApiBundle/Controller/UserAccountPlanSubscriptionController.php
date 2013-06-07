<?php

namespace SimplyTestable\ApiBundle\Controller;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use SimplyTestable\ApiBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;

class UserAccountPlanSubscriptionController extends AbstractUserController
{
    //const DEFAULT_ACCOUNT_PLAN_NAME = 'basic';
    
//    public function __construct() {
////        $this->setInputDefinitions(array(
////            'createAction' => new InputDefinition(array(
////                new InputArgument('email', InputArgument::REQUIRED, 'User email address'),
////                new InputArgument('password', InputArgument::REQUIRED, 'User password')
////            ))
////        ));
////        
////        $this->setRequestTypes(array(
////            'createAction' => \Guzzle\Http\Message\Request::POST
////        ));        
//    }
    
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
        
        $plan = $this->getAccountPlanService()->find($plan_name);
        
        //var_dump($this->getStripeService()->getApiKey());
        
        $this->getStripeService()->subscribe($this->getUser(), $plan);
        
//        $this->container->getParameter('stripe_api_key');
//        
//        \Stripe::setApiKey("sk_test_7KYre2BlBaFZ9NOGYVH43EPo");
//        
//        var_dump($plan_name, $this->container->getParameter('stripe_api_key'));
        exit();
        
//        $email = $this->get('request')->get('email');
//        var_dump($email);
//        exit();
        
        
//        $email = $this->getArguments('createAction')->get('email');
//        $password = $this->getArguments('createAction')->get('password');        
//        
//        if ($this->getUserService()->exists($email)) {
//            $user = $this->getUserService()->findUserByEmail($email);
//            
//            if ($user->isEnabled()) {
//                return $this->redirect($this->generateUrl('user', array(
//                    'email_canonical' => $email
//                ), true));                
//            }           
//        }
//        
//        $user = $this->getUserService()->create($email, $password);
//        
//        if ($user instanceof User) {
//            $plan = $this->getAccountPlanService()->find(self::DEFAULT_ACCOUNT_PLAN_NAME);        
//            $this->getUserAccountPlanService()->create($user, $plan);            
//        }
//        
//        return new \Symfony\Component\HttpFoundation\Response();
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
