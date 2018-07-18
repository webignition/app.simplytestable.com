<?php
namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use App\Entity\UserPostActivationProperties;
use App\Entity\User;
use App\Entity\Account\Plan\Plan as AccountPlan;

class UserPostActivationPropertiesService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EntityRepository
     */
    private $userPostActivationPropertiesRepository;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->userPostActivationPropertiesRepository = $entityManager->getRepository(
            UserPostActivationProperties::class
        );
    }

    /**
     * @param User $user
     * @param AccountPlan $accountPlan
     * @param string|null $coupon
     * @return UserPostActivationProperties
     */
    public function create(User $user, AccountPlan $accountPlan, $coupon = null)
    {
        $userPostActivationProperties = $this->userPostActivationPropertiesRepository->findOneBy([
            'user' => $user,
        ]);

        if (empty($userPostActivationProperties)) {
            $userPostActivationProperties = new UserPostActivationProperties();
            $userPostActivationProperties->setUser($user);
        }

        $userPostActivationProperties->setAccountPlan($accountPlan);
        $userPostActivationProperties->setCoupon($coupon);

        $this->entityManager->persist($userPostActivationProperties);
        $this->entityManager->flush();

        return $userPostActivationProperties;
    }
}
