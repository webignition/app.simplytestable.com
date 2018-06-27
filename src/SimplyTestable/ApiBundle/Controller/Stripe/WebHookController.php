<?php

namespace SimplyTestable\ApiBundle\Controller\Stripe;

use Doctrine\ORM\EntityManagerInterface;
use SimplyTestable\ApiBundle\Entity\Stripe\Event;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;
use SimplyTestable\ApiBundle\Resque\Job\Stripe\ProcessEventJob;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\ApiBundle\Services\StripeEventService;
use SimplyTestable\ApiBundle\Services\StripeWebHookMailNotificationSender;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class WebHookController
{
    /**
     * @param EntityManagerInterface $entityManager
     * @param StripeEventService $stripeEventService
     * @param ResqueQueueService $resqueQueueService
     * @param StripeWebHookMailNotificationSender $stripeWebHookMailNotification
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function indexAction(
        EntityManagerInterface $entityManager,
        StripeEventService $stripeEventService,
        ResqueQueueService $resqueQueueService,
        StripeWebHookMailNotificationSender $stripeWebHookMailNotification,
        Request $request
    ) {
        $eventContent = $this->getEventContent($request);

        if (empty($eventContent)) {
            throw new BadRequestHttpException();
        }

        $userAccountPlanRepository = $entityManager->getRepository(UserAccountPlan::class);
        $stripeEventRepository = $entityManager->getRepository(Event::class);

        $requestData = json_decode($eventContent);
        $stripeId = $requestData->id;


        $stripeEvent = $stripeEventRepository->findOneBy([
            'stripeId' => $stripeId,
        ]);

        if (!empty($stripeEvent)) {
            return new JsonResponse($stripeEvent);
        }

        $stripeWebHookMailNotification->send($eventContent, $requestData->type);

        $stripeCustomer = $this->getStripeCustomerFromEventData($requestData->data);

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

        $resqueQueueService->enqueue(new ProcessEventJob(['stripeId' => $stripeEvent->getStripeId()]));

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
}
