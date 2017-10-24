<?php

namespace SimplyTestable\ApiBundle\Controller\Stripe;

use SimplyTestable\ApiBundle\Controller\ApiController;
use SimplyTestable\ApiBundle\Entity\Stripe\Event as StripeEvent;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;
use SimplyTestable\ApiBundle\Services\Mail\Service as MailService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WebHookController extends ApiController
{
    /**
     * @param Request $request
     *
     * @return JsonResponse|Response
     */
    public function indexAction(Request $request)
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $stripeEventService = $this->container->get('simplytestable.services.stripeeventservice');
        $mailService = $this->get('simplytestable.services.mail.service');

        $eventContent = $this->getEventContent($request);

        if (empty($eventContent)) {
            return $this->sendFailureResponse();
        }

        $requestData = json_decode($eventContent);
        $stripeId = $requestData->id;

        $stripeEventRepository = $entityManager->getRepository(StripeEvent::class);
        $stripeEvent = $stripeEventRepository->findOneBy([
            'stripeId' => $stripeId,
        ]);

        if (!empty($stripeEvent)) {
            return $this->sendResponse($stripeEvent);
        }

        $this->sendDeveloperWebhookNotification($mailService, $eventContent, $requestData->type);

        $stripeCustomer = $this->getStripeCustomerFromEventData($requestData->data);

        $userAccountPlanRepository = $entityManager->getRepository(UserAccountPlan::class);
        $userAccountPlan = $userAccountPlanRepository->findOneBy([
            'stripeCustomer' => $stripeCustomer,
        ]);

        $user = (empty($userAccountPlan))
            ? null
            : $userAccountPlan->getUser();

        $stripeEvent = $stripeEventService->create(
            $stripeId,
            $requestData->type,
            $requestData->livemode,
            $eventContent,
            $user
        );

        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');
        $resqueJobFactory = $this->container->get('simplytestable.services.resque.jobfactory');

        $resqueQueueService->enqueue(
            $resqueJobFactory->create(
                'stripe-event',
                ['stripeId' => $stripeEvent->getStripeId()]
            )
        );

        return new JsonResponse($stripeEvent);
    }

    /**
     * @param \stdClass $eventData
     *
     * @return null|string
     */
    private function getStripeCustomerFromEventData(\stdClass $eventData)
    {
        $eventDataObject = $eventData->object;

        if (isset($eventDataObject->customer)) {
            return $eventDataObject->customer;
        }

        return $eventDataObject->id;
    }

    /**
     * @param Request $request
     *
     * @return null|string
     */
    private function getEventContent(Request $request)
    {
        $requestContent = trim($request->getContent());

        if ($this->isStripeEventContent($requestContent)) {
            return $requestContent;
        }

        $eventParameter = trim($request->request->get('event'));
        if ($this->isStripeEventContent($eventParameter)) {
            return $eventParameter;
        }

        return null;
    }

    /**
     * @param string $string
     *
     * @return bool
     */
    private function isStripeEventContent($string)
    {
        $string = trim($string);
        if (empty($string)) {
            return false;
        }

        $event = json_decode($string);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        if (!isset($event->object)) {
            return false;
        }

        return $event->object == 'event';
    }

    /**
     * @param MailService $mailService
     * @param string $rawWebhookData
     * @param string $eventType
     */
    private function sendDeveloperWebhookNotification(MailService $mailService, $rawWebhookData, $eventType)
    {
        $emailSettings = $this->container->getParameter('stripe_webhook_developer_notification');

        $message = $mailService->getNewMessage();
        $message->setFrom($emailSettings['sender_email'], $emailSettings['sender_name']);
        $message->addTo($emailSettings['recipient_email']);
        $message->setSubject(str_replace('{{ event-type }}', $eventType, $emailSettings['subject']));
        $message->setTextMessage($rawWebhookData);

        $mailService->getSender()->send($message);
    }
}
