<?php

namespace AppBundle\EventListener\Stripe;

use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Request;
use AppBundle\Event\Stripe\DispatchableEvent;
use AppBundle\Services\StripeEventService;

abstract class AbstractListener
{
    /**
     * @var StripeEventService
     */
    protected $stripeEventService;

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var DispatchableEvent
     */
    protected $event;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var string
     */
    private $webClientStripeWebHookUrl;

    /**
     * @param StripeEventService $stripeEventService
     * @param HttpClient $httpClient
     * @param EntityManagerInterface $entityManager
     * @param string $webClientStripeWebHookUrl
     */
    public function __construct(
        StripeEventService $stripeEventService,
        HttpClient $httpClient,
        EntityManagerInterface $entityManager,
        $webClientStripeWebHookUrl
    ) {
        $this->stripeEventService = $stripeEventService;
        $this->httpClient = $httpClient;
        $this->entityManager = $entityManager;
        $this->webClientStripeWebHookUrl = $webClientStripeWebHookUrl;
    }

    /**
     * @param DispatchableEvent $event
     */
    protected function setEvent(DispatchableEvent $event)
    {
        $this->event = $event;
    }

    protected function getDefaultWebClientData()
    {
        return [
            'event' => $this->event->getEntity()->getType(),
            'user' => $this->event->getEntity()->getUser()->getEmail()
        ];
    }

    protected function markEntityProcessed()
    {
        $this->event->getEntity()->setIsProcessed(true);

        $this->entityManager->persist($this->event->getEntity());
        $this->entityManager->flush();
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    protected function issueWebClientEvent($data)
    {
        $request = new Request(
            'POST',
            $this->webClientStripeWebHookUrl,
            ['content-type' => 'application/x-www-form-urlencoded'],
            http_build_query($data, '', '&')
        );

        try {
            $this->httpClient->send($request);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
