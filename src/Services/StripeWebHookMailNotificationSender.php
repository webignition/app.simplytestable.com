<?php

namespace App\Services;

use Postmark\Models\DynamicResponseModel;
use Postmark\Models\PostmarkException;
use Postmark\PostmarkClient;
use Psr\Log\LoggerInterface;

class StripeWebHookMailNotificationSender
{
    /**
     * @var PostmarkClient
     */
    private $postmarkClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @param PostmarkClient $postmarkClient
     * @param LoggerInterface $logger
     * @param $parameters
     */
    public function __construct(
        PostmarkClient $postmarkClient,
        LoggerInterface $logger,
        $parameters
    ) {
        $this->postmarkClient = $postmarkClient;
        $this->logger = $logger;
        $this->parameters = $parameters;
    }

    /**
     * @param string $rawWebHookData
     * @param string $eventType
     *
     * @return DynamicResponseModel
     */
    public function send($rawWebHookData, $eventType)
    {
        try {
            return $this->postmarkClient->sendEmail(
                $this->parameters['sender_email'],
                $this->parameters['recipient_email'],
                str_replace('{{ event-type }}', $eventType, $this->parameters['subject']),
                null,
                $rawWebHookData
            );
        } catch (PostmarkException $postmarkException) {
            $this->logger->error(sprintf(
                'Postmark failure [%s] [%s]',
                $postmarkException->httpStatusCode,
                $postmarkException->postmarkApiErrorCode
            ), [
                'message' => $postmarkException->getMessage(),
            ]);
        }
    }
}
