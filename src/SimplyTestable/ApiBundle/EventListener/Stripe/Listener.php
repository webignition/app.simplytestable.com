<?php

namespace SimplyTestable\ApiBundle\EventListener\Stripe;

use Symfony\Component\HttpKernel\Log\LoggerInterface as Logger;

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
     * @param Logger $logger
     */
    public function __construct(
            Logger $logger,
            \SimplyTestable\ApiBundle\Services\StripeService $stripeService,
            \SimplyTestable\ApiBundle\Services\StripeEventService $stripeEventService,
            \SimplyTestable\ApiBundle\Services\UserAccountPlanService $userAccountPlanService,
            \SimplyTestable\ApiBundle\Services\HttpClientService $httpClientService,
            $webClientProperties
    ) {        
        $this->logger = $logger;
        $this->stripeService = $stripeService;
        $this->stripeEventService = $stripeEventService;
        $this->userAccountPlanService = $userAccountPlanService;
        $this->httpClientService = $httpClientService;
        $this->webClientProperties = $webClientProperties;
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent $event
     * @return \SimplyTestable\ApiBundle\Entity\UserAccountPlan
     */
    private function getUserAccountPlanFromEvent(\SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent $event) {
        return $this->userAccountPlanService->getForUser($event->getEntity()->getUser());   
    }
    
    private function getStripeCustomerFromEvent(\SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent $event) {
        return $this->stripeService->getCustomer($this->getUserAccountPlanFromEvent($event));
    }
    
    private function getDefaultWebClientData(\SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent $event) {        
        return array(
            'event' => $event->getEntity()->getType(),
            'user' => $event->getEntity()->getUser()->getEmail()
        );
    }
    
    /**
     * 
     * @param array $stripeCustomer
     * @return boolean
     */
    private function getStripeCustomerHasCard($stripeCustomer) {
        return isset($stripeCustomer['active_card']) && !is_null($stripeCustomer['active_card']);
    }
    
    public function onCustomerSubscriptionCreated(\SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent $event) {
        $status = $event->getEntity()->getStripeEventDataObject()->data->object->status;        
        $stripeCustomer = $this->getStripeCustomerFromEvent($event);
        
        $webClientData = array_merge($this->getDefaultWebClientData($event), array(
            'status' => $status,
            'has_card' => (int)$this->getStripeCustomerHasCard($stripeCustomer),
            'plan_name' => $event->getEntity()->getStripeEventDataObject()->data->object->plan->name
        ));
        
        if ($status == 'trialing') {            
            $webClientData = array_merge($webClientData, array(
                'trial_start' => $event->getEntity()->getStripeEventDataObject()->data->object->trial_start,
                'trial_end' => $event->getEntity()->getStripeEventDataObject()->data->object->trial_end,
                'trial_period_days' => $event->getEntity()->getStripeEventDataObject()->data->object->plan->trial_period_days
            ));        
        }
        
        if ($status == 'active') {            
            $webClientData = array_merge($webClientData, array(
                'current_period_start' => $event->getEntity()->getStripeEventDataObject()->data->object->current_period_start,
                'current_period_end' => $event->getEntity()->getStripeEventDataObject()->data->object->current_period_end,
                'amount' => $event->getEntity()->getStripeEventDataObject()->data->object->plan->amount
            ));             
        }
        
        $this->issueWebClientEvent($webClientData);        
        $this->markEntityProcessed($event);
    }
    
    
    public function onCustomerSubscriptionTrialWillEnd(\SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent $event) {        
        $stripeCustomer = $this->getStripeCustomerFromEvent($event);
        
        $this->issueWebClientEvent(array_merge($this->getDefaultWebClientData($event), array(
            'trial_end' => $event->getEntity()->getStripeEventDataObject()->data->object->trial_end,
            'has_card' => (int)$this->getStripeCustomerHasCard($stripeCustomer)           
        )));     
        
        $this->markEntityProcessed($event);
    }      
    
    
    public function onCustomerSubscriptionUpdated(\SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent $event) {      
        $eventData = $event->getEntity()->getStripeEventDataObject();
        
        $isPlanChange = (isset($eventData->data->previous_attributes) && isset($eventData->data->previous_attributes->plan));
        
        $webClientEventData = $this->getDefaultWebClientData($event);
        
        if ($isPlanChange) {
            $webClientEventData = array_merge(
                $webClientEventData,
                array(
                    'is_plan_change' => 1,
                    'old_plan' => $eventData->data->previous_attributes->plan->name,
                    'new_plan' => $eventData->data->object->plan->name,
                    'new_amount' => $eventData->data->object->plan->amount,
                    'subscription_status' => $eventData->data->object->status                    
                )
            );
        }
        
        $isStatusChange = (isset($eventData->data->previous_attributes->status));
        
        if ($isStatusChange) {
            $statusTransition = $eventData->data->previous_attributes->status . '-to-' . $eventData->data->object->status;
            
            switch ($statusTransition) {
                case 'active-to-canceled':
                case 'past_due-to-canceled':
                    $webClientEventData = array_merge(
                        $webClientEventData,
                        array(
                            'is_status_change' => 1,
                            'previous_subscription_status' => $eventData->data->previous_attributes->status,
                            'subscription_status' => $eventData->data->object->status
                        )
                    );
                    break;
                
                case 'trialing-to-active':
                    $stripeCustomer = $this->getStripeCustomerFromEvent($event);
                    $webClientEventData = array_merge(
                        $webClientEventData,
                        array(  
                            'is_status_change' => 1,
                            'previous_subscription_status' => $eventData->data->previous_attributes->status,
                            'subscription_status' => $eventData->data->object->status,
                            'has_card' => (int)$this->getStripeCustomerHasCard($stripeCustomer)
                        )
                    );
                    break;  
                
                case 'trialing-to-canceled':
                    $webClientEventData = array_merge(
                        $webClientEventData,
                        array(   
                            'is_status_change' => 1,
                            'previous_subscription_status' => $eventData->data->previous_attributes->status,
                            'subscription_status' => $eventData->data->object->status,
                            'trial_days_remaining' => $this->getUserAccountPlanFromEvent($event)->getStartTrialPeriod()
                        )
                    );
                    break;                
                
                default:
                    $this->markEntityProcessed($event);
                    return;
            }            
        }
        
        $this->issueWebClientEvent($webClientEventData);       
        $this->markEntityProcessed($event);
    }
        
    
    public function onInvoiceCreated(\SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent $event) {
        $total = $event->getEntity()->getStripeEventDataObject()->data->object->total;
        
        if ($total == 0) {
            $this->markEntityProcessed($event);
            return;
        }
      
        if ($this->getStripeCustomerHasCard($this->getStripeCustomerFromEvent($event))) {
            $this->markEntityProcessed($event);
            return;            
        }
        
        $this->issueWebClientEvent(array_merge($this->getDefaultWebClientData($event), array(
            'plan_name' => $event->getEntity()->getStripeEventDataObject()->data->object->lines->data[0]->plan->name,
            'next_payment_attempt' => $event->getEntity()->getStripeEventDataObject()->data->object->next_payment_attempt,
            'invoice_id' => $event->getEntity()->getStripeEventDataObject()->data->object->id
        )));
        
        $this->markEntityProcessed($event);  
    }    
    
    public function onInvoicePaymentFailed(\SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent $event) {        
        $webClientData = array_merge($this->getDefaultWebClientData($event), array(
            'has_card' => ((int)$this->getStripeCustomerHasCard($this->getStripeCustomerFromEvent($event))),
            'attempt_count' => $event->getEntity()->getStripeEventDataObject()->data->object->attempt_count,
            'attempt_limit' => 4,
            'invoice_id' => $event->getEntity()->getStripeEventDataObject()->data->object->id
        ));
        
        if (isset($event->getEntity()->getStripeEventDataObject()->data->object->next_payment_attempt) && !is_null($event->getEntity()->getStripeEventDataObject()->data->object->next_payment_attempt)) {
            $webClientData['next_payment_attempt'] = $event->getEntity()->getStripeEventDataObject()->data->object->next_payment_attempt;
        }
        
        $this->issueWebClientEvent($webClientData);       
        $this->markEntityProcessed($event);
    }
    
    public function onInvoicePaymentSucceeded(\SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent $event) {        
        $total = $event->getEntity()->getStripeEventDataObject()->data->object->total;

        if ($total == 0) {
            $this->markEntityProcessed($event);
            return;
        }        
        
        $this->issueWebClientEvent(array_merge($this->getDefaultWebClientData($event), array(
            'plan_name' => $event->getEntity()->getStripeEventDataObject()->data->object->lines->data[0]->plan->name,
            'plan_amount' => $event->getEntity()->getStripeEventDataObject()->data->object->lines->data[0]->plan->amount,
            'invoice_total' => $total,
            'period_start' => $event->getEntity()->getStripeEventDataObject()->data->object->lines->data[0]->period->start,
            'period_end' => $event->getEntity()->getStripeEventDataObject()->data->object->lines->data[0]->period->end,
            'invoice_id' => $event->getEntity()->getStripeEventDataObject()->data->object->id
        )));

        
        $this->markEntityProcessed($event);    
    }     
    
    
    private function markEntityProcessed(\SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent $event) {
        $event->getEntity()->setIsProcessed(true);
        $this->stripeEventService->persistAndFlush($event->getEntity());
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

}