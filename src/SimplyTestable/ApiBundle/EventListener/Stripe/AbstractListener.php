<?php

namespace SimplyTestable\ApiBundle\EventListener\Stripe;

use Doctrine\ORM\EntityManagerInterface;
use SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent;
use SimplyTestable\ApiBundle\Services\HttpClientService;
use SimplyTestable\ApiBundle\Services\StripeEventService;

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
     * @var HttpClientService
     */
    private $httpClientService;

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
     * @param HttpClientService $httpClientService
     * @param EntityManagerInterface $entityManager
     * @param $webClientProperties
     */
    public function __construct(
        StripeEventService $stripeEventService,
        HttpClientService $httpClientService,
        EntityManagerInterface $entityManager,
        $webClientProperties
    ) {
        $this->stripeEventService = $stripeEventService;
        $this->httpClientService = $httpClientService;
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

        try {
            $request = $this->httpClientService->postRequest($subscriberUrl, [
                'body' => $data,
            ]);

            $this->httpClientService->get()->send($request);
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
