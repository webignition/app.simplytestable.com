<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use SimplyTestable\ApiBundle\Entity\Stripe\Event as StripeEvent;
use SimplyTestable\ApiBundle\Entity\User;

class StripeEventService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EntityRepository
     */
    private $entityRepository;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->entityRepository = $entityManager->getRepository(StripeEvent::class);
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
        $existingEvent = $this->entityRepository->findOneBy([
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

        $this->entityManager->persist($stripeEvent);
        $this->entityManager->flush();

        return $stripeEvent;
    }

    /**
     * @param User $user
     * @param string|string[] $type
     *
     * @return StripeEvent[]|null
     */
    public function getForUserAndType(User $user, $type)
    {
        if (is_string($type)) {
            $type = trim($type);
        }

        return $this->entityRepository->findBy([
            'user' => $user,
            'type' => $type,
        ], [
            'id' => 'DESC'
        ]);
    }
}
