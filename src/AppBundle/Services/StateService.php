<?php
namespace AppBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use AppBundle\Entity\State;

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
     * @var State[]
     */
    private $states = [];

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->stateRepository = $entityManager->getRepository(State::class);
    }

    /**
     * @param string $name
     *
     * @return State
     */
    public function get($name)
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
    public function getCollection($stateNames)
    {
        $states = [];

        foreach ($stateNames as $stateName) {
            $states[$stateName] = $this->get($stateName);
        }

        return $states;
    }
}
