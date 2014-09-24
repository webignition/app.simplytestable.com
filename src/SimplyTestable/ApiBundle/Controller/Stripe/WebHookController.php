<?php

namespace SimplyTestable\ApiBundle\Controller\Stripe;

use SimplyTestable\ApiBundle\Controller\ApiController;

class WebHookController extends ApiController {

    public function indexAction() {
        if (!$this->hasEventContent()) {
            return $this->sendFailureResponse();
        }
        
        $requestBody = $this->getEventContent();
        $requestData = json_decode($this->getEventContent());               
        
        $stripeId = $requestData->id;
        
        if ($this->getStripeEventService()->has($stripeId)) {
            return $this->sendResponse($this->getStripeEventService()->getByStripeId($stripeId));
        }        
        
        $this->sendDeveloperWebhookNotification($requestBody, $requestData->type);
        
        $stripeCustomer = $this->getStripeCustomerFromEventData($requestData->data);        
        $user = $this->getUserAccountPlanService()->getUserByStripeCustomer($stripeCustomer);
        
        $stripeEvent = $this->getStripeEventService()->create($stripeId, $requestData->type, $requestData->livemode, $requestBody, $user);

        $this->getResqueQueueService()->enqueue(
            $this->getResqueJobFactoryService()->create(
                'stripe-event',
                ['stripeId' => $stripeEvent->getStripeId()]
            )
        );
        
        return $this->sendResponse($stripeEvent);
    }
    
    private function getStripeCustomerFromEventData(\stdClass $eventData) {
        if (!isset($eventData->object)) {
            return null;
        }
        
        $eventDataObject = $eventData->object;
        
        if (isset($eventDataObject->customer)) {
            return $eventDataObject->customer;
        }
        
        if ($eventDataObject->object == 'customer') {
            return $eventDataObject->id;
        }
    }
    
    private function getEventContent() {
        $requestContent = trim($this->get('request')->getContent());        
        if ($this->isStripeEventContent($requestContent)) {
            return $requestContent;
        }
        
        $eventParameter = trim($this->get('request')->request->get('event'));
        if ($this->isStripeEventContent($eventParameter)) {
            return $eventParameter;
        }
        
        return null;       
    }
    
    
    /**
     * 
     * @return boolean
     */
    private function hasEventContent() {
        return !is_null($this->getEventContent());
    }
    
    
    /**
     * 
     * @param string $string
     * @return boolean
     */
    private function isStripeEventContent($string) {
        if (!$this->isNonEmptyJson($string)) {
            return false;
        }
        
        $event = json_decode($string);
        if (!isset($event->object)) {
            return false;
        }
        
        return $event->object == 'event';
    }
    
    
    /**
     * 
     * @param string $string
     * @return boolean
     */
    private function isNonEmptyJson($string) {
        $string = trim($string);
        if ($string == '') {
            return false;
        }
        
        return $this->isJson($string);
    }

    
    /**
     * 
     * @param string $string
     * @return boolean
     */
    private function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    private function sendDeveloperWebhookNotification($rawWebhookData, $eventType) {
        $emailSettings = $this->container->getParameter('stripe_webhook_developer_notification');

        $message = $this->getMailService()->getNewMessage();
        $message->setFrom($emailSettings['sender_email'], $emailSettings['sender_name']);
        $message->addTo($emailSettings['recipient_email']);
        $message->setSubject(str_replace('{{ event-type }}', $eventType, $emailSettings['subject']));
        $message->setTextMessage($rawWebhookData);
        
        $this->getMailService()->getSender()->send($message);                
    }
    

    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\UserAccountPlanService
     */
    private function getUserAccountPlanService() {
        return $this->container->get('simplytestable.services.useraccountplanservice');
    }    

    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\StripeEventService
     */
    private function getStripeEventService() {
        return $this->container->get('simplytestable.services.stripeeventservice');
    }


    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\Resque\QueueService
     */
    private function getResqueQueueService() {
        return $this->container->get('simplytestable.services.resque.queueService');
    }


    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\Resque\JobFactoryService
     */
    private function getResqueJobFactoryService() {
        return $this->container->get('simplytestable.services.resque.jobFactoryService');
    }

    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Services\Mail\Service
     */
    private function getMailService() {
        return $this->get('simplytestable.services.mail.service');
    }      

}
