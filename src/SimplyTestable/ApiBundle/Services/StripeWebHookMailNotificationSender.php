<?php

namespace SimplyTestable\ApiBundle\Services;

use SimplyTestable\ApiBundle\Services\Mail\Service as MailService;

class StripeWebHookMailNotificationSender
{
    /**
     * @var MailService
     */
    private $mailService;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @param MailService $mailService
     * @param $parameters
     */
    public function __construct(
        MailService $mailService,
        $parameters
    ) {
        $this->mailService = $mailService;
        $this->parameters = $parameters;
    }

    /**
     * @param string $rawWebHookData
     * @param string $eventType
     */
    public function send($rawWebHookData, $eventType)
    {
        $message = $this->mailService->getNewMessage();
        $message->setFrom($this->parameters['sender_email'], $this->parameters['sender_name']);
        $message->addTo($this->parameters['recipient_email']);
        $message->setSubject(str_replace('{{ event-type }}', $eventType, $this->parameters['subject']));
        $message->setTextMessage($rawWebHookData);

        $this->mailService->getSender()->send($message);
    }
}
