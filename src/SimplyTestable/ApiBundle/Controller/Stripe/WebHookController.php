<?php

namespace SimplyTestable\ApiBundle\Controller\Stripe;

use SimplyTestable\ApiBundle\Controller\ApiController;
use Symfony\Component\HttpFoundation\Response;

class WebHookController extends ApiController
{  
    
    
    public function indexAction()
    {        
        $requestBody = $this->get('request')->getContent();
        $requestData = json_decode($requestBody);
        
        $this->sendDeveloperWebhookNotification($requestBody, $requestData->type);
        
        $this->getStripeEventService()->create($requestData->id, $requestData->type, $requestData->livemode);
        
        return new Response();   
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
