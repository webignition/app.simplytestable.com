<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Exception\Services\UserAccountPlan\Exception as UserAccountPlanServiceException;

class UserAccountPlanSubscriptionController extends AbstractUserController
{

    public function __construct() {
        $this->setRequestTypes(array(
            'subscribeAction' => \Guzzle\Http\Message\Request::POST
        ));
    }

    public function subscribeAction($email_canonical, $plan_name)
    {
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        if ($this->getApplicationStateService()->isInMaintenanceBackupReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        if ($this->getUserService()->isPublicUser($this->getUser())) {
            return $this->sendFailureResponse();
        }

        if ($email_canonical !== $this->getUser()->getEmail()) {
            return $this->sendFailureResponse();
        }

        if (!$this->getAccountPlanService()->has($plan_name)) {
            return $this->sendFailureResponse();
        }

        try {
            $this->getUserAccountPlanService()->subscribe($this->getUser(), $this->getAccountPlanService()->find($plan_name));
        } catch (\Stripe_AuthenticationError $stripeAuthenticationError) {
            return $this->sendForbiddenResponse();
        } catch (\Stripe_CardError $stripeCardError) {
            $this->getUserAccountPlanService()->removeCurrentForUser($this->getUser());
            return $this->sendFailureResponse(array(
                'X-Stripe-Error-Message' => $stripeCardError->getMessage(),
                'X-Stripe-Error-Param' => $stripeCardError->param,
                'X-Stripe-Error-Code' => $stripeCardError->getCode()
            ));
        } catch (UserAccountPlanServiceException $userAccountPlanServiceException) {
            if ($userAccountPlanServiceException->isUserIsTeamMemberException()) {
                return $this->sendFailureResponse(array(
                    'X-Error-Message' => 'User is a team member',
                    'X-Error-Code' => $userAccountPlanServiceException->getCode()
                ));
            }

            throw $userAccountPlanServiceException;
        }

        return $this->sendSuccessResponse();
    }

    public function associateCardAction($email_canonical, $stripe_card_token) {
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        if ($this->getUserService()->isPublicUser($this->getUser())) {
            return $this->sendFailureResponse();
        }

        if ($email_canonical !== $this->getUser()->getEmail()) {
            return $this->sendFailureResponse();
        }

        if (!$this->isValidStripeCardToken($stripe_card_token)) {
            return $this->sendFailureResponse();
        }

        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($this->getUser());
        if (!$userAccountPlan->hasStripeCustomer()) {
            return $this->sendFailureResponse();
        }

        try {
            $this->getStripeService()->updateCustomer($userAccountPlan, array(
                'card' => $stripe_card_token
            ));
        } catch (\Stripe_CardError $stripeCardError) {
            return $this->sendFailureResponse(array(
                'X-Stripe-Error-Message' => $stripeCardError->getMessage(),
                'X-Stripe-Error-Param' => $stripeCardError->param,
                'X-Stripe-Error-Code' => $stripeCardError->getCode()
            ));
        }

        return $this->sendSuccessResponse();
    }

    /**
     *
     * @param string $token
     * @return boolean
     */
    private function isValidStripeCardToken($token) {
        return preg_match('/tok_[a-z0-9]{14}/i', $token) > 0;
    }

    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\StripeService
     */
    private function getStripeService() {
        return $this->get('simplytestable.services.stripeservice');
    }


    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\AccountPlanService
     */
    private function getAccountPlanService() {
        return $this->get('simplytestable.services.accountplanservice');
    }


    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\UserAccountPlanService
     */
    private function getUserAccountPlanService() {
        return $this->get('simplytestable.services.useraccountplanservice');
    }

}
