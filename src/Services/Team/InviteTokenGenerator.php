<?php
namespace App\Services\Team;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use App\Entity\Team\Invite;

class InviteTokenGenerator implements TokenGeneratorInterface
{
    /**
     * @var EntityRepository
     */
    private $teamInviteRepository;

    /**

     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->teamInviteRepository = $entityManager->getRepository(Invite::class);
    }

    /**
     * {@inheritdoc}
     */
    public function generateToken()
    {
        $token = md5(rand());

        $invite = $this->teamInviteRepository->findOneBy([
            'token' => $token
        ]);

        if (!empty($invite)) {
            return $this->generateToken();
        }

        return $token;
    }
}
