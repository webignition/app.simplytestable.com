<?php
namespace SimplyTestable\ApiBundle\Services;

use SimplyTestable\ApiBundle\Entity\Stripe\Event as StripeEvent;
use SimplyTestable\ApiBundle\Entity\User;

class StripeEventService extends EntityService
{
    /**
     * @return string
     */
    protected function getEntityName()
    {
        return StripeEvent::class;
    }

    /**
     * @param string $stripeId
     * @param string $type
     * @param bool $isLiveMode
     * @param string $data
     * @param User $user
     *
     * @return StripeEvent
     */
    public function create($stripeId, $type, $isLiveMode, $data, $user = null)
    {
        /* @var StripeEvent $existingEvent */
        $existingEvent = $this->getEntityRepository()->findOneBy([
            'stripeId' => $stripeId,
        ]);

        if (!empty($existingEvent)) {
            return $existingEvent;
        }

        $stripeEvent = new StripeEvent();

        $stripeEvent->setStripeId($stripeId);
        $stripeEvent->setType($type);
        $stripeEvent->setIsLive($isLiveMode);
        $stripeEvent->setStripeEventData($data);

        if (!is_null($user)) {
            $stripeEvent->setUser($user);
        }

        return $this->persistAndFlush($stripeEvent);
    }

    /**
     * @param User $user
     * @param mixed $type
     *
     * @return StripeEvent[]|null
     */
    public function getForUserAndType(User $user, $type = null)
    {
        $criteria = array(
            'user' => $user
        );

        if (is_string($type)) {
            $type = trim($type);
            if ($type != '') {
                $criteria['type'] = $type;
            }
        }

        if (is_array($type) && count($type)) {
            $criteria['type'] = $type;
        }

        return $this->getEntityRepository()->findBy($criteria, array(
            'id' => 'DESC'
        ));
    }

    /**
     * @param StripeEvent $stripeEvent
     *
     * @return StripeEvent
     */
    public function persistAndFlush(StripeEvent $stripeEvent)
    {
        $this->getManager()->persist($stripeEvent);
        $this->getManager()->flush();

        return $stripeEvent;
    }
}
