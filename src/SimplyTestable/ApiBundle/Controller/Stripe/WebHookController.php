<?php

namespace SimplyTestable\ApiBundle\Controller\Stripe;

use SimplyTestable\ApiBundle\Controller\ApiController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class WebHookController extends ApiController {

    public function indexAction() {
        if (!$this->hasEventContent()) {
            return $this->sendFailureResponse();
        }
        
        $requestBody = $this->getEventContent();
        $requestData = json_decode($this->getEventContent());
        
        $this->sendDeveloperWebhookNotification($requestBody, $requestData->type);        
        $stripeEvent = $this->getStripeEventService()->create($requestData->id, $requestData->type, $requestData->livemode);
        
        return $this->sendResponse($stripeEvent);
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

        $message = \Swift_Message::newInstance();

        $message->setSubject(str_replace('{{ event-type }}', $eventType, $emailSettings['subject']));
        $message->setFrom($emailSettings['sender_email'], $emailSettings['sender_name']);
        $message->setTo($emailSettings['recipient_email']);
        $message->setBody($rawWebhookData);

        $this->get('mailer')->send($message);
    }

    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\StripeEventService
     */
    private function getStripeEventService() {
        return $this->container->get('simplytestable.services.stripeeventservice');
    }

}
