<?php

namespace App\Tests\Factory;

use App\Entity\Job\Type;
use ReflectionClass;
use App\Entity\Account\Plan\Constraint;
use App\Entity\Account\Plan\Plan as AccountPlan;
use App\Entity\CrawlJobContainer;
use App\Entity\Job\Ammendment;
use App\Entity\Job\Job;
use App\Entity\Job\RejectionReason;
use App\Entity\Job\TaskConfiguration;
use App\Entity\Job\TaskTypeOptions;
use App\Entity\Job\Type as JobType;
use App\Entity\State;
use App\Entity\Task\Output;
use App\Entity\Task\Type\Type as TaskType;
use App\Entity\TimePeriod;
use App\Entity\User;
use App\Entity\UserAccountPlan;
use App\Entity\WebSite;
use App\Entity\Worker;
use App\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use App\Request\Job\ListRequest;
use Stripe\Customer as StripeCustomer;
use Stripe\Stripe;
use webignition\InternetMediaType\InternetMediaType;
use webignition\Model\Stripe\Customer as StripeCustomerModel;

class ModelFactory
{
    const USER_EMAIL = 'email';
    const WEBSITE_CANONICAL_URL = 'canonical-url';
    const JOB_TYPE_NAME = 'name';
    const TASK_CONFIGURATION_TYPE = 'type';
    const TASK_CONFIGURATION_OPTIONS = 'options';
    const TASK_CONFIGURATION_IS_ENABLED = 'is-enabled';
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
    const TASK_TYPE_OPTIONS_TASK_TYPE = 'task-type';
    const TASK_TYPE_OPTIONS_TASK_OPTIONS = 'options';
    const JOB_ID = 'id';
    const JOB_USER = 'user';
    const JOB_WEBSITE = 'website';
    const JOB_STATE = 'state';
    const JOB_URL_COUNT = 'url-count';
    const JOB_REQUESTED_TASK_TYPES = 'requested-task-types';
    const JOB_TASK_TYPE_OPTIONS_COLLECTION = 'task-type-options-collection';
    const JOB_TYPE = 'type';
    const JOB_PARAMETERS = 'parameters';
    const JOB_TIME_PERIOD = 'time-period';
    const REJECTION_REASON_REASON = 'reason';
    const REJECTION_REASON_CONSTRAINT = 'constraint';
    const AMMENDMENT_REASON = 'reason';
    const AMMENDMENT_CONSTRAINT = 'constraint';
    const JOB_LIST_REQUEST_TYPES_TO_EXCLUDE = 'types-to-exclude';
    const JOB_LIST_REQUEST_STATES_TO_EXCLUDE = 'states-to-exclude';
    const JOB_LIST_REQUEST_URL_FILTER = 'url-filter';
    const JOB_LIST_REQUEST_JOB_IDS_TO_EXCLUDE = 'job-ids-to-exclude';
    const JOB_LIST_REQUEST_JOB_IDS_TO_INCLUDE = 'job-ids-to-include';
    const JOB_LIST_REQUEST_USER = 'user';
    const CRAWL_JOB_CONTAINER_PARENT_JOB = 'parent-job';
    const CRAWL_JOB_CONTAINER_CRAWL_JOB = 'crawl-job';

    /**
     * @param array $userValues
     *
     * @return User
     */
    public static function createUser($userValues = [])
    {
        $user = new User();

        if (!isset($userValues[self::USER_EMAIL])) {
            $userValues[self::USER_EMAIL] = 'user@example.com';
        }

        $user->setEmail($userValues[self::USER_EMAIL]);
        $user->setEmailCanonical($userValues[self::USER_EMAIL]);
        $user->setUsername($userValues[self::USER_EMAIL]);
        $user->setUsernameCanonical($userValues[self::USER_EMAIL]);

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

        foreach ($taskConfigurationCollectionValues as $taskConfigurationValues) {
            $taskConfiguration = self::createTaskConfiguration($taskConfigurationValues);
            $taskConfigurationCollection->add($taskConfiguration);
        }

        return $taskConfigurationCollection;
    }

    /**
     * @param array $taskConfigurationValues
     *
     * @return TaskConfiguration
     */
    public static function createTaskConfiguration($taskConfigurationValues)
    {
        $taskType = self::createTaskType([
            self::TASK_TYPE_NAME => $taskConfigurationValues[self::TASK_CONFIGURATION_TYPE],
        ]);

        $taskConfiguration = new TaskConfiguration();
        $taskConfiguration->setType($taskType);

        if (isset($taskConfigurationValues[self::TASK_CONFIGURATION_OPTIONS])) {
            $taskConfiguration->setOptions($taskConfigurationValues[self::TASK_CONFIGURATION_OPTIONS]);
        }

        if (isset($taskConfigurationValues[self::TASK_CONFIGURATION_IS_ENABLED])) {
            $taskConfiguration->setIsEnabled($taskConfigurationValues[self::TASK_CONFIGURATION_IS_ENABLED]);
        }

        return $taskConfiguration;
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
    public static function createStripeCustomerModel(
        $fixtureName,
        $fixtureReplacements = [],
        $fixtureModifications = []
    ) {
        return new StripeCustomerModel(
            StripeApiFixtureFactory::load($fixtureName, $fixtureReplacements, $fixtureModifications)
        );
    }

    /**
     * @param array $taskTypeOptionsValues
     *
     * @return TaskTypeOptions
     */
    public static function createTaskTypeOptions($taskTypeOptionsValues)
    {
        $taskTypeOptions = new TaskTypeOptions();

        $taskTypeOptions->setTaskType($taskTypeOptionsValues[self::TASK_TYPE_OPTIONS_TASK_TYPE]);
        $taskTypeOptions->setOptions($taskTypeOptionsValues[self::TASK_TYPE_OPTIONS_TASK_OPTIONS]);

        return $taskTypeOptions;
    }

    /**
     * @param array $jobValues
     *
     * @return Job
     */
    public static function createJob($jobValues = [])
    {
        $user = $jobValues[self::JOB_USER] ?? new User();
        $website = $jobValues[self::JOB_WEBSITE] ?? new WebSite();
        $type = $jobValues[self::JOB_TYPE] ?? new Type();
        $state = $jobValues[self::JOB_STATE] ?? new State();
        $parameters = $jobValues[self::JOB_PARAMETERS] ?? '';

        $job = Job::create($user, $website, $type, $state, $parameters);

        if (isset($jobValues[self::JOB_ID])) {
            $reflectionClass = new ReflectionClass(Job::class);

            $reflectionProperty = $reflectionClass->getProperty('id');
            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue($job, $jobValues[self::JOB_ID]);
        }

        if (isset($jobValues[self::JOB_URL_COUNT])) {
            $job->setUrlCount($jobValues[self::JOB_URL_COUNT]);
        }

        if (isset($jobValues[self::JOB_REQUESTED_TASK_TYPES])) {
            $requestedTaskTypes = $jobValues[self::JOB_REQUESTED_TASK_TYPES];

            foreach ($requestedTaskTypes as $taskType) {
                $job->addRequestedTaskType($taskType);
            }
        }

        if (isset($jobValues[self::JOB_TASK_TYPE_OPTIONS_COLLECTION])) {
            $taskTypeOptionsCollection = $jobValues[self::JOB_TASK_TYPE_OPTIONS_COLLECTION];

            foreach ($taskTypeOptionsCollection as $taskTypeOptions) {
                $job->addTaskTypeOption($taskTypeOptions);
            }
        }

        if (isset($jobValues[self::JOB_TIME_PERIOD])) {
            $timePeriod = $jobValues[self::JOB_TIME_PERIOD];

            $job->setStartDateTime($timePeriod->getStartDateTime());
            $job->setEndDateTime($timePeriod->getEndDateTime());
        }

        return $job;
    }

    /**
     * @param array $rejectionReasonValues
     *
     * @return RejectionReason
     */
    public static function createRejectionReason($rejectionReasonValues)
    {
        $rejectionReason = new RejectionReason();

        $rejectionReason->setReason($rejectionReasonValues[self::REJECTION_REASON_REASON]);

        if (isset($rejectionReasonValues[self::REJECTION_REASON_CONSTRAINT])) {
            $rejectionReason->setConstraint($rejectionReasonValues[self::REJECTION_REASON_CONSTRAINT]);
        }

        return $rejectionReason;
    }

    /**
     * @param array $ammendmentValues
     *
     * @return Ammendment
     */
    public static function createAmmendment($ammendmentValues)
    {
        $ammendment = new Ammendment();

        $ammendment->setReason($ammendmentValues[self::AMMENDMENT_REASON]);
        $ammendment->setConstraint($ammendmentValues[self::AMMENDMENT_CONSTRAINT]);

        return $ammendment;
    }

    /**
     * @param array $jobListRequestValues
     *
     * @return ListRequest
     */
    public static function createJobListRequest($jobListRequestValues)
    {
        $jobListRequest = new ListRequest(
            $jobListRequestValues[self::JOB_LIST_REQUEST_TYPES_TO_EXCLUDE],
            $jobListRequestValues[self::JOB_LIST_REQUEST_STATES_TO_EXCLUDE],
            $jobListRequestValues[self::JOB_LIST_REQUEST_URL_FILTER],
            $jobListRequestValues[self::JOB_LIST_REQUEST_JOB_IDS_TO_EXCLUDE],
            $jobListRequestValues[self::JOB_LIST_REQUEST_JOB_IDS_TO_INCLUDE],
            $jobListRequestValues[self::JOB_LIST_REQUEST_USER]
        );

        return $jobListRequest;
    }

    /**
     * @param array $crawlJobContainerValues
     *
     * @return CrawlJobContainer
     */
    public static function createCrawlJobContainer($crawlJobContainerValues = [])
    {
        $crawlJobContainer = new CrawlJobContainer();

        if (isset($crawlJobContainerValues[self::CRAWL_JOB_CONTAINER_PARENT_JOB])) {
            $crawlJobContainer->setParentJob($crawlJobContainerValues[self::CRAWL_JOB_CONTAINER_PARENT_JOB]);
        }

        return $crawlJobContainer;
    }
}
