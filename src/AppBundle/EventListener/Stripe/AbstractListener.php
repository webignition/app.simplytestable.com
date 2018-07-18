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
     * @var array
     */
    private $webClientProperties;

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
     * @param StripeEventService $stripeEventService
     * @param HttpClient $httpClient
     * @param EntityManagerInterface $entityManager
     * @param $webClientProperties
     */
    public function __construct(
        StripeEventService $stripeEventService,
        HttpClient $httpClient,
        EntityManagerInterface $entityManager,
        $webClientProperties
    ) {
        $this->stripeEventService = $stripeEventService;
        $this->httpClient = $httpClient;
        $this->webClientProperties = $webClientProperties;
        $this->entityManager = $entityManager;
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
        $subscriberUrl = $this->getWebClientSubscriberUrl();
        if (is_null($subscriberUrl)) {
            return false;
        }

        $request = new Request(
            'POST',
            $subscriberUrl,
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

    /**
     * @return string|null
     */
    private function getWebClientSubscriberUrl()
    {
        if (!isset($this->webClientProperties['urls'])) {
            return null;
        }

        if (!isset($this->webClientProperties['urls']['base'])) {
            return null;
        }

        if (!isset($this->webClientProperties['urls']['stripe_event_controller'])) {
            return null;
        }

        $webClientUrls = $this->webClientProperties['urls'];

        return $webClientUrls['base'] . $webClientUrls['stripe_event_controller'];
    }
}
