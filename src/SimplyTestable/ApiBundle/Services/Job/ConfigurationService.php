<?php
namespace SimplyTestable\ApiBundle\Services\Job;

use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration as TaskConfiguration;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Entity\Job\Type as JobType;
use SimplyTestable\ApiBundle\Services\EntityService;
use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use Doctrine\ORM\EntityManager;

class ConfigurationService extends EntityService {

    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\Job\Configuration';

    /**
     * @var TeamService
     */
    private $teamService;


    /**
     * @var User
     */
    private $user;

    /**
     * @param EntityManager $entityManager
     * @param TeamService $teamService
     */
    public function __construct(EntityManager $entityManager,TeamService $teamService) {
        parent::__construct($entityManager);
        $this->teamService = $teamService;
    }

    /**
     *
     * @return string
     */
    protected function getEntityName() {
        return self::ENTITY_NAME;
    }

    /**
     *
     * @return \SimplyTestable\ApiBundle\Repository\Job\ConfigurationRepository
     */
    public function getEntityRepository() {
        return parent::getEntityRepository();
    }


    /**
     * @param User $user
     * @return $this
     */
    public function setUser(User $user) {
        $this->user = $user;
        return $this;
    }


    public function create(WebSite $website, JobType $type, $taskConfigurations = [], $label = '', $parameters = '') {
        if (!$this->hasUser()) {
            throw new JobConfigurationServiceException(
                'User is not set',
                JobConfigurationServiceException::CODE_USER_NOT_SET
            );
        }

        $label = trim($label);

        if ($this->hasForLabel($label)) {
            throw new JobConfigurationServiceException(
                'Label "' . $label . '" is not unique',
                JobConfigurationServiceException::CODE_LABEL_NOT_UNIQUE
            );
        }



        if ($this->hasExisting($website, $type, $taskConfigurations, $parameters)) {
            throw new JobConfigurationServiceException(
                'Matching configuration already exists',
                JobConfigurationServiceException::CONFIGURATION_ALREADY_EXISTS
            );
        }

        $jobConfiguration = new JobConfiguration();
        $jobConfiguration->setLabel($label);
        $jobConfiguration->setUser($this->user);
        $jobConfiguration->setWebsite($website);
        $jobConfiguration->setType($type);
        $jobConfiguration->setParameters($parameters);

        $this->getManager()->persist($jobConfiguration);

        foreach ($taskConfigurations as $taskConfiguration) {
            /* @var $taskConfiguration TaskConfiguration */
            $taskConfiguration->setJobConfiguration($jobConfiguration);
            $jobConfiguration->addTaskConfiguration($taskConfiguration);
            $this->getManager()->persist($taskConfiguration);
        }

        $this->getManager()->persist($jobConfiguration);
        $this->getManager()->flush();

        return $jobConfiguration;
    }

    public function get() {
        return null;
    }

    public function update() {
        return null;
    }

    public function delete() {
        return null;
    }

    public function getList() {
        return null;
    }


    /**
     * @param $label
     * @return bool
     */
    private function hasForLabel($label) {
        return !is_null($this->getByLabel($label));
    }


    /**
     * @param $label
     * @return null|JobConfiguration
     */
    private function getByLabel($label) {
        $byUser = $this->getEntityRepository()->findOneBy([
            'label' => $label,
            'user' => $this->user
        ]);

        if (!is_null($byUser)) {
            return $byUser;
        }

        if (!$this->teamService->hasForUser($this->user)) {
            return null;
        }

        $people = $this->teamService->getPeopleForUser($this->user);

        foreach ($people as $teamPerson) {
            $entity = $this->getEntityRepository()->findOneBy([
                'label' => $label,
                'user' => $teamPerson
            ]);

            if (!is_null($entity)) {
                return $entity;
            }
        }

        return null;
    }


    /**
     * @param WebSite $website
     * @param JobType $type
     * @param TaskConfiguration[] $taskConfigurations
     * @param string $parameters
     * @return bool
     */
    private function hasExisting( WebSite $website, JobType $type, $taskConfigurations = [], $parameters = '') {
        if ($this->getEntityRepository()->getCountByProperties($this->user, $website, $type, $parameters) === 0) {
            return false;
        }

        /* @var $jobConfigurations JobConfiguration[] */
        $jobConfigurations = $this->getEntityRepository()->findBy([
            'user' => $this->user,
            'website' => $website,
            'type' => $type,
            'parameters' => $parameters
        ]);

        foreach ($jobConfigurations as $jobConfiguration) {
            if ($this->areTaskConfigurationCollectionsEqual($taskConfigurations, $jobConfiguration->getTaskConfigurations())) {
                return true;
            }
        }

        return false;
    }


    /**
     * @param TaskConfiguration[] $source
     * @param TaskConfiguration[] $comparator
     * @return bool
     */
    private function areTaskConfigurationCollectionsEqual($source = [], $comparator  = []) {
        if (count($source) != count($comparator)) {
            return false;
        }

        $matchCount = 0;

        /* @var $taskConfiguration TaskConfiguration */
        foreach ($source as $taskConfiguration) {
            if ($this->taskConfigurationCollectionContainsTaskConfiguration($comparator, $taskConfiguration)) {
                $matchCount++;
            }
        }

        return $matchCount == count($source);
    }


    /**
     * @param TaskConfiguration[] $taskConfigurationCollection
     * @param TaskConfiguration $taskConfiguration
     * @return bool
     */
    private function taskConfigurationCollectionContainsTaskConfiguration($taskConfigurationCollection = [], TaskConfiguration $taskConfiguration) {
        /* @var $sourceTaskConfiguration TaskConfiguration */
        foreach ($taskConfigurationCollection as $sourceTaskConfiguration) {
            if ($sourceTaskConfiguration->hasMatchingTypeAndOptions($taskConfiguration)) {
                return true;
            }
        }

        return false;
    }


    /**
     * @return bool
     */
    private function hasUser() {
        return $this->user instanceof User;
    }



}