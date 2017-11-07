<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use SimplyTestable\ApiBundle\Entity\State;

class StateService
{
    const EXCEPTION_MESSAGE_UNKNOWN_STATE = 'Unknown state "%s"';
    const EXCEPTION_CODE_UNKNOWN_STATE = 1;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EntityRepository
     */
    private $stateRepository;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->stateRepository = $entityManager->getRepository(State::class);
    }

    /**
     * @var State[]
     */
    private $states = [];

    /**
     * @param string $name
     *
     * @return State
     */
    public function fetch($name)
    {
        if (!isset($this->states[$name])) {
            $state = $this->stateRepository->findOneBy([
                'name' => $name,
            ]);

            if (empty($state)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        self::EXCEPTION_MESSAGE_UNKNOWN_STATE,
                        $name
                    ),
                    self::EXCEPTION_CODE_UNKNOWN_STATE
                );
            }

            $this->states[$name] = $state;
        }

        return $this->states[$name];
    }

    /**
     * @param string[] $stateNames
     *
     * @return State[]
     */
    public function fetchCollection($stateNames)
    {
        $states = [];

        foreach ($stateNames as $stateName) {
            $states[$stateName] = $this->fetch($stateName);
        }

        return $states;
    }
}
