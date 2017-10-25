<?php

namespace SimplyTestable\ApiBundle\Tests\Factory;

use SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan as AccountPlan;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Entity\Job\Type as JobType;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\Task\Output;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;
use SimplyTestable\ApiBundle\Entity\TimePeriod;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;
use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use Stripe\Customer as StripeCustomer;
use Stripe\Stripe;
use webignition\InternetMediaType\InternetMediaType;
use webignition\Model\Stripe\Customer as StripeCustomerModel;

class ModelFactory
{
    const USER_EMAIL = 'email';
    const WEBSITE_CANONICAL_URL = 'canonical-url';
    const JOB_TYPE_NAME = 'name';
    const TASK_CONFIGURATION_COLLECTION_TYPE = 'type';
    const TASK_CONFIGURATION_COLLECTION_OPTIONS = 'options';
    const TASK_TYPE_NAME = 'name';
    const TIME_PERIOD_START_DATE_TIME = 'start-date-time';
    const TIME_PERIOD_END_DATE_TIME = 'end-date-time';
    const TASK_OUTPUT_OUTPUT = 'output';
    const TASK_OUTPUT_CONTENT_TYPE = 'content-type';
    const TASK_OUTPUT_ERROR_COUNT = 'error-count';
    const TASK_OUTPUT_WARNING_COUNT = 'warning-count';
    const USER_ACCOUNT_PLAN_PLAN = 'plan';
    const USER_ACCOUNT_PLAN_START_TRIAL_PERIOD = 'start-trial-period';
    const USER_ACCOUNT_PLAN_STRIPE_CUSTOMER = 'stripe-customer';
    const ACCOUNT_PLAN_NAME = 'name';
    const ACCOUNT_PLAN_IS_PREMIUM  = 'is-premium';
    const ACCOUNT_PLAN_CONSTRAINTS = 'constraints';
    const CONSTRAINT_NAME = 'name';
    const CONSTRAINT_LIMIT = 'limit';

    /**
     * @param array $userValues
     *
     * @return User
     */
    public static function createUser($userValues)
    {
        $user = new User();

        $user->setEmail($userValues[self::USER_EMAIL]);
        $user->setEmailCanonical($userValues[self::USER_EMAIL]);

        return $user;
    }

    /**
     * @param array $websiteValues
     *
     * @return WebSite
     */
    public static function createWebsite($websiteValues)
    {
        $website = new WebSite();

        $website->setCanonicalUrl($websiteValues[self::WEBSITE_CANONICAL_URL]);

        return $website;
    }

    /**
     * @param $jobTypeValues
     *
     * @return JobType
     */
    public static function createJobType($jobTypeValues)
    {
        $jobType = new JobType();

        $jobType->setName($jobTypeValues[self::JOB_TYPE_NAME]);

        return $jobType;
    }

    /**
     * @param array $taskConfigurationCollectionValues
     *
     * @return TaskConfigurationCollection
     */
    public static function createTaskConfigurationCollection($taskConfigurationCollectionValues = [])
    {
        $taskConfigurationCollection = new TaskConfigurationCollection();

        foreach ($taskConfigurationCollectionValues as $taskTypeName => $taskConfigurationValues) {
            $taskType = self::createTaskType([
                self::TASK_TYPE_NAME => $taskTypeName,
            ]);

            $taskConfiguration = new TaskConfiguration();
            $taskConfiguration->setType($taskType);

            if (isset($taskConfigurationValues[self::TASK_CONFIGURATION_COLLECTION_OPTIONS])) {
                $taskConfiguration->setOptions($taskConfigurationValues[self::TASK_CONFIGURATION_COLLECTION_OPTIONS]);
            }

            $taskConfigurationCollection->add($taskConfiguration);
        }

        return $taskConfigurationCollection;
    }

    /**
     * @param array $taskTypeValues
     *
     * @return TaskType
     */
    public static function createTaskType($taskTypeValues)
    {
        $taskType = new TaskType();

        $taskType->setName($taskTypeValues[self::TASK_TYPE_NAME]);

        return $taskType;
    }

    /**
     * @param string $stripeCustomerId
     * @param string $stripeCustomerJson
     *
     * @return StripeCustomer
     */
    public static function createStripeCustomer($stripeCustomerId, $stripeCustomerJson)
    {
        StripeApiFixtureFactory::set(
            [$stripeCustomerJson]
        );

        Stripe::setApiKey('foo');

        $stripeCustomer = StripeCustomer::retrieve($stripeCustomerId);

        return $stripeCustomer;
    }

    /**
     * @param string $name
     * @param State|null $nextState
     *
     * @return State
     */
    public static function createState($name, $nextState = null)
    {
        $state = new State();
        $state->setName($name);
        $state->setNextState($nextState);

        return $state;
    }

    /**
     * @param string $hostname
     * @param State $state
     * @param string $token
     *
     * @return Worker
     */
    public static function createWorker($hostname, State $state, $token)
    {
        $worker = new Worker();
        $worker->setHostname($hostname);
        $worker->setState($state);
        $worker->setToken($token);

        return $worker;
    }

    /**
     * @param array $timePeriodValues
     *
     * @return TimePeriod
     */
    public static function createTimePeriod($timePeriodValues)
    {
        $timePeriod = new TimePeriod();
        $timePeriod->setStartDateTime($timePeriodValues[self::TIME_PERIOD_START_DATE_TIME]);
        $timePeriod->setEndDateTime($timePeriodValues[self::TIME_PERIOD_END_DATE_TIME]);

        return $timePeriod;
    }

    /**
     * @param array $taskOutputValues
     *
     * @return Output
     */
    public static function createTaskOutput($taskOutputValues)
    {
        $output = new Output();

        $contentTypeParts = explode('/', $taskOutputValues[self::TASK_OUTPUT_CONTENT_TYPE]);
        $contentType = new InternetMediaType();
        $contentType->setType($contentTypeParts[0]);
        $contentType->setSubtype($contentTypeParts[1]);

        $output->setOutput($taskOutputValues[self::TASK_OUTPUT_OUTPUT]);
        $output->setContentType($contentType);
        $output->setErrorCount($taskOutputValues[self::TASK_OUTPUT_ERROR_COUNT]);
        $output->setWarningCount($taskOutputValues[self::TASK_OUTPUT_WARNING_COUNT]);

        return $output;
    }

    /**
     * @param array $constraintValues
     *
     * @return Constraint
     */
    public static function createAccountPlanConstraint($constraintValues)
    {
        $constraint = new Constraint();

        $constraint->setName($constraintValues[self::CONSTRAINT_NAME]);
        $constraint->setLimit($constraintValues[self::CONSTRAINT_LIMIT]);

        return $constraint;
    }

    /**
     * @param array $accountPlanValues
     *
     * @return AccountPlan
     */
    public static function createAccountPlan($accountPlanValues)
    {
        $accountPlan = new AccountPlan();

        $accountPlan->setName($accountPlanValues[self::ACCOUNT_PLAN_NAME]);
        $accountPlan->setIsPremium($accountPlanValues[self::ACCOUNT_PLAN_IS_PREMIUM]);

        if (isset($accountPlanValues[self::ACCOUNT_PLAN_CONSTRAINTS])) {
            $constraints = $accountPlanValues[self::ACCOUNT_PLAN_CONSTRAINTS];

            foreach ($constraints as $constraint) {
                $accountPlan->addConstraint($constraint);
            }
        }

        return $accountPlan;
    }

    /**
     * @param array $userAccountPlanValues
     *
     * @return UserAccountPlan
     */
    public static function createUserAccountPlan($userAccountPlanValues)
    {
        $userAccountPlan = new UserAccountPlan();

        $userAccountPlan->setPlan($userAccountPlanValues[self::USER_ACCOUNT_PLAN_PLAN]);
        $userAccountPlan->setStartTrialPeriod($userAccountPlanValues[self::USER_ACCOUNT_PLAN_START_TRIAL_PERIOD]);

        if (isset($userAccountPlanValues[self::USER_ACCOUNT_PLAN_STRIPE_CUSTOMER])) {
            $userAccountPlan->setStripeCustomer($userAccountPlanValues[self::USER_ACCOUNT_PLAN_STRIPE_CUSTOMER]);
        }

        return $userAccountPlan;
    }

    /**
     * @param string $fixtureName
     * @param array $fixtureReplacements
     * @param array $fixtureModifications
     *
     * @return StripeCustomerModel
     */
    public static function createStripeCustomerModel($fixtureName, $fixtureReplacements = [], $fixtureModifications = [])
    {
        return new StripeCustomerModel(
            StripeApiFixtureFactory::load($fixtureName, $fixtureReplacements, $fixtureModifications)
        );
    }
}
