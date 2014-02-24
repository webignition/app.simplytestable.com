<?php

namespace SimplyTestable\ApiBundle\EventListener\Stripe;

use Symfony\Component\HttpKernel\Log\LoggerInterface as Logger;

use SimplyTestable\ApiBundle\Model\Stripe\Invoice as StripeInvoice;
use SimplyTestable\ApiBundle\Model\Stripe\Customer as StripeCustomer;

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
     * @return \SimplyTestable\ApiBundle\Model\Stripe\Invoice
     */
    private function getStripeInvoice() {
        return new StripeInvoice($this->getEventEntity()->getStripeEventDataObject()->data->object);
    }
    
    private function getDefaultWebClientData() {        
        return array(
            'event' => $this->getEventEntity()->getType(),
            'user' => $this->getEventEntity()->getUser()->getEmail()
        );
    }
    
//    /**
//     * 
//     * @param array $stripeCustomer
//     * @return boolean
//     */
//    private function getStripeCustomerHasCard($stripeCustomer) {
//        return isset($stripeCustomer['active_card']) && !is_null($stripeCustomer['active_card']);
//    }
    
    public function onCustomerSubscriptionCreated(\SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent $event) {
        $this->event = $event;
        
        $status = $this->getEventEntity()->getStripeEventDataObject()->data->object->status;        
        $stripeCustomer = $this->getStripeCustomer();
        
        $webClientData = array_merge($this->getDefaultWebClientData(), array(
            'status' => $status,
            'has_card' => (int)$this->getStripeCustomerHasCard($stripeCustomer),
            'plan_name' => $this->getEventEntity()->getStripeEventDataObject()->data->object->plan->name
        ));
        
        if ($status == 'trialing') {            
            $webClientData = array_merge($webClientData, array(
                'trial_start' => $this->getEventEntity()->getStripeEventDataObject()->data->object->trial_start,
                'trial_end' => $this->getEventEntity()->getStripeEventDataObject()->data->object->trial_end,
                'trial_period_days' => $this->getEventEntity()->getStripeEventDataObject()->data->object->plan->trial_period_days
            ));        
        }
        
        if ($status == 'active') {            
            $webClientData = array_merge($webClientData, array(
                'current_period_start' => $this->getEventEntity()->getStripeEventDataObject()->data->object->current_period_start,
                'current_period_end' => $this->getEventEntity()->getStripeEventDataObject()->data->object->current_period_end,
                'amount' => $this->getEventEntity()->getStripeEventDataObject()->data->object->plan->amount
            ));             
        }
        
        $this->issueWebClientEvent($webClientData);        
        $this->markEntityProcessed();
    }
    
    
    public function onCustomerSubscriptionTrialWillEnd(\SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent $event) {        
        $this->event = $event;
        
        $stripeCustomer = $this->getStripeCustomer();
        
        $this->issueWebClientEvent(array_merge($this->getDefaultWebClientData(), array(
            'trial_end' => $this->getEventEntity()->getStripeEventDataObject()->data->object->trial_end,
            'has_card' => (int)$this->getStripeCustomerHasCard($stripeCustomer),
            'plan_amount' => $this->getEventEntity()->getStripeEventDataObject()->data->object->plan->amount,
            'plan_name' => $this->getEventEntity()->getStripeEventDataObject()->data->object->plan->name
        )));     
        
        $this->markEntityProcessed();
    }      
    
    
    public function onCustomerSubscriptionUpdated(\SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent $event) {      
        $this->event = $event;
        
        $eventData = $this->getEventEntity()->getStripeEventDataObject();
        
        $isPlanChange = (isset($eventData->data->previous_attributes) && isset($eventData->data->previous_attributes->plan));
        
        $webClientEventData = $this->getDefaultWebClientData();
        
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
            
            if ($eventData->data->object->status == 'trialing') {
                $webClientEventData['trial_end'] = $eventData->data->object->trial_end;
            }
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
                    $stripeCustomer = $this->getStripeCustomer();
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
                            'trial_days_remaining' => $this->getUserAccountPlanFromEvent()->getStartTrialPeriod()
                        )
                    );
                    break;                
                
                default:
                    $this->markEntityProcessed();
                    return;
            }            
        }
        
        $this->issueWebClientEvent($webClientEventData);       
        $this->markEntityProcessed();
    }
        
    
    public function onInvoiceCreated(\SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent $event) {        
        $this->event = $event; 
        
        $invoice = $this->getStripeInvoice();
        
        if ($invoice->getTotal() === 0 && $invoice->getAmountDue() === 0) {
            $this->markEntityProcessed();
            return;
        }
      
        if ($this->getStripeCustomer()->hasCard()) {
            $this->markEntityProcessed();
            return;            
        }
        
        $this->issueWebClientEvent(array_merge($this->getDefaultWebClientData(), array(
            'lines' => $invoice->getLinesSummary(),
            'next_payment_attempt' => $invoice->getNextPaymentAttempt(),
            'invoice_id' => $invoice->getId(),
            'total' => $invoice->getTotal(),
            'amount_due' => $invoice->getAmountDue()
        )));
        
        $this->markEntityProcessed();  
    }
    
    public function onInvoicePaymentFailed(\SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent $event) {
        $this->event = $event;
        
        $webClientData = array_merge($this->getDefaultWebClientData(), array(
            'plan_name' => $this->getEventEntity()->getStripeEventDataObject()->data->object->lines->data[0]->plan->name,
            'has_card' => ((int)$this->getStripeCustomerHasCard($this->getStripeCustomer())),
            'attempt_count' => $this->getEventEntity()->getStripeEventDataObject()->data->object->attempt_count,
            'attempt_limit' => 4,
            'invoice_id' => $this->getEventEntity()->getStripeEventDataObject()->data->object->id,
            'amount_due' => $this->getEventEntity()->getStripeEventDataObject()->data->object->amount_due
        ));
        
        if (isset($this->getEventEntity()->getStripeEventDataObject()->data->object->next_payment_attempt) && !is_null($this->getEventEntity()->getStripeEventDataObject()->data->object->next_payment_attempt)) {
            $webClientData['next_payment_attempt'] = $this->getEventEntity()->getStripeEventDataObject()->data->object->next_payment_attempt;
        }
        
        $this->issueWebClientEvent($webClientData);       
        $this->markEntityProcessed();
    }
    
    public function onInvoicePaymentSucceeded(\SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent $event) {        
        $this->event = $event;
        
        $total = $this->getEventEntity()->getStripeEventDataObject()->data->object->total;

        if ($total == 0) {
            $this->markEntityProcessed();
            return;
        }        
        
        $this->issueWebClientEvent(array_merge($this->getDefaultWebClientData(), array(
            'plan_name' => $this->getEventEntity()->getStripeEventDataObject()->data->object->lines->data[0]->plan->name,
            'plan_amount' => $this->getEventEntity()->getStripeEventDataObject()->data->object->lines->data[0]->plan->amount,
            'invoice_total' => $total,
            'period_start' => $this->getEventEntity()->getStripeEventDataObject()->data->object->lines->data[0]->period->start,
            'period_end' => $this->getEventEntity()->getStripeEventDataObject()->data->object->lines->data[0]->period->end,
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

}