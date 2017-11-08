<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use FOS\UserBundle\Util\CanonicalizerInterface;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use SimplyTestable\ApiBundle\Entity\UserEmailChangeRequest;
use SimplyTestable\ApiBundle\Entity\User;

class UserEmailChangeRequestService
{
    /**
     * @var CanonicalizerInterface
     */
    private $emailCanonicalizer;

    /**
     * @var TokenGeneratorInterface
     */
    private $tokenGenerator;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EntityRepository
     */
    private $userEmailChangeRequestRepository;

    /**
     * @param EntityManager $entityManager
     * @param CanonicalizerInterface $emailCanonicalizer
     * @param TokenGeneratorInterface $tokenGenerator
     */
    public function __construct(
        EntityManager $entityManager,
        CanonicalizerInterface $emailCanonicalizer,
        TokenGeneratorInterface $tokenGenerator
    ) {
        $this->entityManager = $entityManager;
        $this->emailCanonicalizer = $emailCanonicalizer;
        $this->tokenGenerator = $tokenGenerator;

        $this->userEmailChangeRequestRepository = $entityManager->getRepository(UserEmailChangeRequest::class);
    }

    /**
     * @param User $user
     *
     * @return UserEmailChangeRequest
     */
    public function getForUser(User $user)
    {
        return $this->userEmailChangeRequestRepository->findOneBy([
            'user' => $user,
        ]);
    }

    /**
     * @param User $user
     */
    public function removeForUser(User $user)
    {
        $emailChangeRequest = $this->getForUser($user);

        if (!empty($emailChangeRequest)) {
            $this->entityManager->remove($emailChangeRequest);
            $this->entityManager->flush();
        }
    }

    /**
     * @param User $user
     * @param string $newEmail
     *
     * @return UserEmailChangeRequest
     */
    public function create(User $user, $newEmail)
    {
        $emailChangeRequest = $this->getForUser($user);

        if (!empty($emailChangeRequest)) {
            return $this->getForUser($user);
        }

        $emailChangeRequest = new UserEmailChangeRequest();
        $emailChangeRequest->setNewEmail($newEmail);
        $emailChangeRequest->setToken($this->tokenGenerator->generateToken());
        $emailChangeRequest->setUser($user);

        $this->entityManager->persist($emailChangeRequest);
        $this->entityManager->flush();

        return $emailChangeRequest;
    }
}
