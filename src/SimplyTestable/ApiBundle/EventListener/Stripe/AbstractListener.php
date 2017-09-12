<?php

namespace SimplyTestable\ApiBundle\EventListener\Stripe;

use Psr\Log\LoggerInterface;
use SimplyTestable\ApiBundle\Entity\Stripe\Event;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;
use SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent;
use SimplyTestable\ApiBundle\Services\AccountPlanService;
use SimplyTestable\ApiBundle\Services\HttpClientService;
use SimplyTestable\ApiBundle\Services\StripeEventService;
use SimplyTestable\ApiBundle\Services\StripeService;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use webignition\Model\Stripe\Customer as StripeCustomer;

abstract class AbstractListener
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var StripeService
     */
    private $stripeService;

    /**
     * @var StripeEventService
     */
    private $stripeEventService;

    /**
     * @var UserAccountPlanService
     */
    private $userAccountPlanService;

    /**
     * @var array
     */
    private $webClientProperties;

    /**
     * @var HttpClientService
     */
    private $httpClientService;

    /**
     * @var AccountPlanService
     */
    private $accountPlanService;

    /**
     * @var DispatchableEvent
     */
    private $event;

    /**
     * @param LoggerInterface $logger
     * @param EventDispatcherInterface $dispatcher
     * @param StripeService $stripeService
     * @param StripeEventService $stripeEventService
     * @param UserAccountPlanService $userAccountPlanService
     * @param HttpClientService $httpClientService
     * @param AccountPlanService $accountPLanService
     * @param $webClientProperties
     */
    public function __construct(
        LoggerInterface $logger,
        EventDispatcherInterface $dispatcher,
        StripeService $stripeService,
        StripeEventService $stripeEventService,
        UserAccountPlanService $userAccountPlanService,
        HttpClientService $httpClientService,
        AccountPlanService $accountPLanService,
        $webClientProperties
    ) {
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;
        $this->stripeService = $stripeService;
        $this->stripeEventService = $stripeEventService;
        $this->userAccountPlanService = $userAccountPlanService;
        $this->httpClientService = $httpClientService;
        $this->accountPlanService = $accountPLanService;
        $this->webClientProperties = $webClientProperties;
    }

    /**
     * @param DispatchableEvent $event
     */
    protected function setEvent(DispatchableEvent $event)
    {
        $this->event = $event;
    }

    /**
     * @return StripeEventService
     */
    protected function getStripeEventService()
    {
        return $this->stripeEventService;
    }

    /**
     * @return UserAccountPlanService
     */
    protected function getUserAccountPlanService()
    {
        return $this->userAccountPlanService;
    }

    /**
     * @return UserAccountPlan
     */
    protected function getUserAccountPlanFromEvent()
    {
        return $this->getUserAccountPlanService()->getForUser($this->getEventEntity()->getUser());
    }

    /**
     * @return Event
     */
    protected function getEventEntity()
    {
        return $this->event->getEntity();
    }

    /**
     * @return StripeCustomer
     */
    protected function getStripeCustomer()
    {
        return $this->stripeService->getCustomer($this->getUserAccountPlanFromEvent($this->event));
    }

    protected function getDefaultWebClientData()
    {
        return array(
            'event' => $this->getEventEntity()->getType(),
            'user' => $this->getEventEntity()->getUser()->getEmail()
        );
    }

    protected function markEntityProcessed()
    {
        $this->getEventEntity()->setIsProcessed(true);
        $this->getStripeEventService()->persistAndFlush($this->getEventEntity());
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

    protected function downgradeToBasicPlan()
    {
        $this->getUserAccountPlanService()->subscribe(
            $this->event->getEntity()->getUser(),
            $this->accountPlanService->find('basic')
        );
    }
}
