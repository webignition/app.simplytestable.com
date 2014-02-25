<?php

namespace SimplyTestable\ApiBundle\EventListener\Stripe;

use Symfony\Component\HttpKernel\Log\LoggerInterface as Logger;

use SimplyTestable\ApiBundle\Model\Stripe\Invoice\Invoice as StripeInvoice;
use SimplyTestable\ApiBundle\Model\Stripe\Customer as StripeCustomer;
use SimplyTestable\ApiBundle\Model\Stripe\Subscription as StripeSubscription;
use SimplyTestable\ApiBundle\Model\Stripe\Plan as StripePlan;

class Listener
{
    
    /**
     *
     * @var Logger
     */
    private $logger;
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\StripeService 
     */
    private $stripeService;
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\StripeEventService 
     */
    private $stripeEventService;
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\UserAccountPlanService
     */
    private $userAccountPlanService;
    
    
    /**
     *
     * @var array
     */
    private $webClientProperties;
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\HttpClientService
     */
    private $httpClientService;
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\AccountPlanService 
     */
    private $accountPlanService;
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent 
     */
    private $event;
    
    
    /**
     *
     * @param Logger $logger
     */
    public function __construct(
            Logger $logger,
            \SimplyTestable\ApiBundle\Services\StripeService $stripeService,
            \SimplyTestable\ApiBundle\Services\StripeEventService $stripeEventService,
            \SimplyTestable\ApiBundle\Services\UserAccountPlanService $userAccountPlanService,
            \SimplyTestable\ApiBundle\Services\HttpClientService $httpClientService,
            \SimplyTestable\ApiBundle\Services\AccountPlanService $accountPLanService,
            $webClientProperties
    ) {        
        $this->logger = $logger;
        $this->stripeService = $stripeService;
        $this->stripeEventService = $stripeEventService;
        $this->userAccountPlanService = $userAccountPlanService;
        $this->httpClientService = $httpClientService;
        $this->accountPlanService = $accountPLanService;
        $this->webClientProperties = $webClientProperties;
    }
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Entity\Stripe\Event
     */
    private function getEventEntity() {
        return $this->event->getEntity();
    }
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Entity\UserAccountPlan
     */
    private function getUserAccountPlanFromEvent() {
        return $this->userAccountPlanService->getForUser($this->getEventEntity()->getUser());   
    }
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Model\Stripe\Customer
     */
    private function getStripeCustomer() {
        return $this->stripeService->getCustomer($this->getUserAccountPlanFromEvent($this->event));
    }
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Model\Stripe\Invoice\Invoice
     */
    private function getStripeInvoice() {
        return new StripeInvoice($this->getEventEntity()->getStripeEventDataObject()->data->object);
    }
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Model\Stripe\Subscription
     */
    private function getStripeSubscription() {
        return new StripeSubscription($this->getEventEntity()->getStripeEventDataObject()->data->object);
    }
    
    
    private function getDefaultWebClientData() {        
        return array(
            'event' => $this->getEventEntity()->getType(),
            'user' => $this->getEventEntity()->getUser()->getEmail()
        );
    }
    
    public function onCustomerSubscriptionCreated(\SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent $event) {
        $this->event = $event;        
     
        
        $stripeSubscription = $this->getStripeSubscription();
        
        $webClientData = array_merge($this->getDefaultWebClientData(), array(
            'status' => $stripeSubscription->getStatus(),            
            'plan_name' => $stripeSubscription->getPlan()->getName()
        ));
        
        if ($stripeSubscription->isTrialing()) {
            $webClientData = array_merge($webClientData, array(
                'has_card' => (int)$this->getStripeCustomer()->hasCard(),
                'trial_start' => $stripeSubscription->getTrialStart(),
                'trial_end' => $stripeSubscription->getTrialEnd(),
                'trial_period_days' => $stripeSubscription->getPlan()->getTrialPeriodDays()
            ));        
        }
        
        if ($stripeSubscription->isActive()) {            
            $webClientData = array_merge($webClientData, array(
                'current_period_start' => $stripeSubscription->getCurrentPeriodStart(),
                'current_period_end' => $stripeSubscription->getCurrentPeriodEnd(),
                'amount' => $stripeSubscription->getPlan()->getAmount()
            ));             
        }
        
        $this->issueWebClientEvent($webClientData);        
        $this->markEntityProcessed();
    }
    
    
    public function onCustomerSubscriptionTrialWillEnd(\SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent $event) {        
        $this->event = $event;
        
        $stripeCustomer = $this->getStripeCustomer();
        $stripeSubscription = $this->getStripeSubscription();
        
        $this->issueWebClientEvent(array_merge($this->getDefaultWebClientData(), array(
            'trial_end' => $stripeSubscription->getTrialEnd(),
            'has_card' => (int)$stripeCustomer->hasCard(),
            'plan_amount' => $stripeSubscription->getPlan()->getAmount(),
            'plan_name' => $stripeSubscription->getPlan()->getName()
        )));     
        
        $this->markEntityProcessed();
    }      
    
    
    public function onCustomerSubscriptionUpdated(\SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent $event) {      
        $this->event = $event;
        
        $eventData = $this->getEventEntity()->getStripeEventDataObject();
        $webClientEventData = $this->getDefaultWebClientData();
        
        $stripeSubscription = $this->getStripeSubscription();
        
        $isPlanChange = (isset($eventData->data->previous_attributes) && isset($eventData->data->previous_attributes->plan));
        
        if ($isPlanChange) {
            $oldPlan = new StripePlan($eventData->data->previous_attributes->plan);
            
            $webClientEventData = array_merge(
                $webClientEventData,
                array(
                    'is_plan_change' => 1,
                    'old_plan' => $oldPlan->getName(),
                    'new_plan' => $stripeSubscription->getPlan()->getName(),
                    'new_amount' => $stripeSubscription->getPlan()->getAmount(),
                    'subscription_status' => $stripeSubscription->getStatus()
                )
            );
            
            if ($stripeSubscription->isTrialing()) {
                $webClientEventData['trial_end'] = $stripeSubscription->getTrialEnd();
            } 
            
            $this->issueWebClientEvent($webClientEventData);       
            $this->markEntityProcessed();            
        }
        
        $isStatusChange = (isset($eventData->data->previous_attributes->status));
        
        if ($isStatusChange) {
            $statusTransition = $eventData->data->previous_attributes->status . '-to-' . $eventData->data->object->status;
            
            switch ($statusTransition) {
                case 'active-to-canceled':
                    $previousSubscription = new StripeSubscription($eventData->data->previous_attributes);                   
                    
                    $webClientEventData = array_merge(
                        $webClientEventData,
                        array(
                            'is_status_change' => 1,
                            'previous_subscription_status' => $previousSubscription->getStatus(),
                            'subscription_status' => $stripeSubscription->getStatus()
                        )
                    );
                    break;
                
                case 'trialing-to-active':
                    $stripeCustomer = $this->getStripeCustomer();
                    $previousSubscription = new StripeSubscription($eventData->data->previous_attributes);
                    
                    $webClientEventData = array_merge(
                        $webClientEventData,
                        array(  
                            'is_status_change' => 1,
                            'previous_subscription_status' => $previousSubscription->getStatus(),
                            'subscription_status' => $stripeSubscription->getStatus(),
                            'has_card' => (int)$stripeCustomer->hasCard()
                        )
                    );
                    
                    if ($stripeCustomer->hasCard() === false) {
                        $this->downgradeToBasicPlan();
                    }
                    break;               
                
                default:
                    $this->markEntityProcessed();
                    return;
            }            
            
            $this->issueWebClientEvent($webClientEventData);       
            $this->markEntityProcessed();            
        }
    }
    
    public function onInvoicePaymentFailed(\SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent $event) {
        $this->event = $event;
        
        if ($this->getStripeCustomer()->hasCard() === false) {
            $this->markEntityProcessed();
            return;
        }
        
        $invoice = $this->getStripeInvoice();
        
        $webClientData = array_merge($this->getDefaultWebClientData(), array(
            'lines' => $invoice->getLinesSummary(),
            'invoice_id' => $invoice->getId(),
            'total' => $invoice->getTotal(),            
            'amount_due' => $invoice->getAmountDue()
        ));
        
        $this->issueWebClientEvent($webClientData);       
        $this->markEntityProcessed();
    }
    
    public function onInvoicePaymentSucceeded(\SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent $event) {        
        $this->event = $event;
        
        $invoice = $this->getStripeInvoice();
        
        if ($invoice->getTotal() === 0 && $invoice->getAmountDue() === 0) {
            $this->markEntityProcessed();
            return;
        }       
        
        $this->issueWebClientEvent(array_merge($this->getDefaultWebClientData(), array(
            'lines' => $invoice->getLinesSummary(),
            'total' => $invoice->getTotal(),
            'amount_due' => $invoice->getAmountDue(),
            'invoice_id' => $this->getEventEntity()->getStripeEventDataObject()->data->object->id
        )));

        
        $this->markEntityProcessed();        
    }     
    
    
    private function markEntityProcessed() {
        $this->getEventEntity()->setIsProcessed(true);
        $this->stripeEventService->persistAndFlush($this->getEventEntity());
    }
    
    private function issueWebClientEvent($data) {        
        $subscriberUrl = $this->getWebClientSubscriberUrl();
        if (is_null($subscriberUrl)) {
            return false;
        }
        
        $request = $this->httpClientService->postRequest($subscriberUrl, array(), $data);
        
        try {
            $request->send();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    
    /**
     * 
     * @return string|null
     */
    private function getWebClientSubscriberUrl() {
        if (!isset($this->webClientProperties['urls'])) {
            return null;
        }
        
        if (!isset($this->webClientProperties['urls']['base'])) {
            return null;
        }
        
        if (!isset($this->webClientProperties['urls']['stripe_event_controller'])) {
            return null;
        }        
        
        return $this->webClientProperties['urls']['base'] . $this->webClientProperties['urls']['stripe_event_controller']; 
    }
    
    
    private function downgradeToBasicPlan() {
        $this->userAccountPlanService->subscribe(
                $this->event->getEntity()->getUser(),
                $this->accountPlanService->find('basic')
        );        
    }

}