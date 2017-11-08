<?php
namespace SimplyTestable\ApiBundle\Services\Team;

use FOS\UserBundle\Util\TokenGeneratorInterface;
use SimplyTestable\ApiBundle\Repository\TeamInviteRepository;

class InviteTokenGenerator implements TokenGeneratorInterface
{
    /**
     * @var TeamInviteRepository
     */
    private $teamInviteRepository;

    /**
     * @param TeamInviteRepository $teamInviteRepository
     */
    public function __construct(TeamInviteRepository $teamInviteRepository)
    {
        $this->teamInviteRepository = $teamInviteRepository;
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
