<?php

namespace SimplyTestable\ApiBundle\EventListener\Stripe;

use Symfony\Component\HttpKernel\Log\LoggerInterface as Logger;

abstract class Listener
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
     * @param \SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent $event
     */
    protected function setEvent(\SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent $event) {
        $this->event = $event;
    }
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Services\StripeEventService
     */
    protected function getStripeEventService() {
        return $this->stripeEventService;
    }
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Entity\Stripe\Event
     */
    protected function getEventEntity() {
        return $this->event->getEntity();
    }
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Entity\UserAccountPlan
     */
    protected function getUserAccountPlanFromEvent() {
        return $this->userAccountPlanService->getForUser($this->getEventEntity()->getUser());   
    }
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Model\Stripe\Customer
     */
    protected function getStripeCustomer() {
        return $this->stripeService->getCustomer($this->getUserAccountPlanFromEvent($this->event));
    }
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Model\Stripe\Invoice\Invoice
     */
    protected function getStripeInvoice() {
        return new \SimplyTestable\ApiBundle\Model\Stripe\Invoice\Invoice(json_encode($this->getEventEntity()->getStripeEventObject()->getDataObject()->getObject()->__toArray()));
    }
    
    
    /**
     * 
     * @return \webignition\Model\Stripe\Subscription
     */
    protected function getStripeSubscription() {
        return $this->getEventEntity()->getStripeEventObject()->getDataObject()->getObject();
    }
    
    
    protected function getDefaultWebClientData() {        
        return array(
            'event' => $this->getEventEntity()->getType(),
            'user' => $this->getEventEntity()->getUser()->getEmail()
        );
    }    
    
    
    protected function markEntityProcessed() {
        $this->getEventEntity()->setIsProcessed(true);
        $this->getStripeEventService()->persistAndFlush($this->getEventEntity());
    }
    
    protected function issueWebClientEvent($data) {        
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
    
    
    protected function downgradeToBasicPlan() {
        $this->userAccountPlanService->subscribe(
                $this->event->getEntity()->getUser(),
                $this->accountPlanService->find('basic')
        );        
    }

}