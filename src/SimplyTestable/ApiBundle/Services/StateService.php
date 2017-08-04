<?php
namespace SimplyTestable\ApiBundle\Services;

use SimplyTestable\ApiBundle\Entity\State;

class StateService extends EntityService
{
    const EXCEPTION_MESSAGE_UNKNOWN_STATE = 'Unknown state "%s"';
    const EXCEPTION_CODE_UNKNOWN_STATE = 1;

    /**
     * @var State[]
     */
    private $states = [];

    /**
     * {@inheritdoc}
     */
    protected function getEntityName()
    {
        return State::class;
    }

    /**
     * @param string $name
     * @return State
     */
    public function fetch($name)
    {
        if (!isset($this->states[$name])) {
            if (!$this->has($name)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        self::EXCEPTION_MESSAGE_UNKNOWN_STATE,
                        $name
                    ),
                    self::EXCEPTION_CODE_UNKNOWN_STATE
                );
            }

            $this->states[$name] = $this->find($name);
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

    /**
     * @param string $name
     *
     * @return State
     */
    private function find($name)
    {
        /* @var State $state */
        $state = $this->getEntityRepository()->findOneBy([
           'name' => $name
        ]);

        return $state;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return !is_null($this->find($name));
    }
}