<?php
namespace AppBundle\Services\Team;

use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use AppBundle\Entity\Team\Invite;
use AppBundle\Repository\TeamInviteRepository;

class InviteTokenGenerator implements TokenGeneratorInterface
{
    /**
     * @var TeamInviteRepository
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
