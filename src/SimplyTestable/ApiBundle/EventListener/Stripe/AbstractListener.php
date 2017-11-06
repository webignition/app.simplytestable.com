<?php

namespace SimplyTestable\ApiBundle\EventListener\Stripe;

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
     * @param StripeEventService $stripeEventService
     * @param HttpClientService $httpClientService
     * @param $webClientProperties
     */
    public function __construct(
        StripeEventService $stripeEventService,
        HttpClientService $httpClientService,
        $webClientProperties
    ) {
        $this->stripeEventService = $stripeEventService;
        $this->httpClientService = $httpClientService;
        $this->webClientProperties = $webClientProperties;
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
        return array(
            'event' => $this->event->getEntity()->getType(),
            'user' => $this->event->getEntity()->getUser()->getEmail()
        );
    }

    protected function markEntityProcessed()
    {
        $this->event->getEntity()->setIsProcessed(true);
        $this->stripeEventService->persistAndFlush($this->event->getEntity());
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

        $request = $this->httpClientService->postRequest($subscriberUrl, array(), $data);

        try {
            $request->send();
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
