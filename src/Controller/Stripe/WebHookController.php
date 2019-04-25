<?php

namespace App\Controller\Stripe;

use App\Entity\UserAccountPlan;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Stripe\Event;
use App\Resque\Job\Stripe\ProcessEventJob;
use App\Services\Resque\QueueService as ResqueQueueService;
use App\Services\StripeEventService;
use App\Services\StripeWebHookMailNotificationSender;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class WebHookController
{
    public function indexAction(
        EntityManagerInterface $entityManager,
        StripeEventService $stripeEventService,
        ResqueQueueService $resqueQueueService,
        StripeWebHookMailNotificationSender $stripeWebHookMailNotification,
        Request $request
    ): JsonResponse {
        $eventContent = $this->getEventContent($request);

        if (empty($eventContent)) {
            throw new BadRequestHttpException();
        }

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
