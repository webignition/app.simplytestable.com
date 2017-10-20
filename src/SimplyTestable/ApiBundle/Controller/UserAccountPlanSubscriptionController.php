<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Exception\Services\UserAccountPlan\Exception as UserAccountPlanServiceException;
use Stripe\Error\Card as StripeCardError;
use Stripe\Error\Authentication as StripeAuthenticationError;
use Symfony\Component\HttpFoundation\Response;

class UserAccountPlanSubscriptionController extends ApiController
{
    /**
     * @param string $email_canonical
     * @param string $plan_name
     *
     * @return Response
     * @throws UserAccountPlanServiceException
     */
    public function subscribeAction($email_canonical, $plan_name)
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $userService = $this->container->get('simplytestable.services.userservice');
        $accountPlanService = $this->get('simplytestable.services.accountplanservice');
        $userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');

        if ($applicationStateService->isInMaintenanceReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        if ($applicationStateService->isInMaintenanceBackupReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        if ($userService->isPublicUser($this->getUser())) {
            return $this->sendFailureResponse();
        }

        if ($email_canonical !== $this->getUser()->getEmail()) {
            return $this->sendFailureResponse();
        }

        $plan = $accountPlanService->find($plan_name);

        if (empty($plan)) {
            return $this->sendFailureResponse();
        }

        try {
            $userAccountPlanService->subscribe($this->getUser(), $plan);
        } catch (StripeAuthenticationError $stripeAuthenticationError) {
            return $this->sendForbiddenResponse();
        } catch (StripeCardError $stripeCardError) {
            $userAccountPlanService->removeCurrentForUser($this->getUser());
            return $this->sendFailureResponse([
                'X-Stripe-Error-Message' => $stripeCardError->getMessage(),
                'X-Stripe-Error-Param' => $stripeCardError->param,
                'X-Stripe-Error-Code' => $stripeCardError->getCode()
            ]);
        } catch (UserAccountPlanServiceException $userAccountPlanServiceException) {
            if ($userAccountPlanServiceException->isUserIsTeamMemberException()) {
                return $this->sendFailureResponse([
                    'X-Error-Message' => 'User is a team member',
                    'X-Error-Code' => $userAccountPlanServiceException->getCode()
                ]);
            }
        }

        return $this->sendSuccessResponse();
    }

    /**
     * @param string $email_canonical
     * @param string $stripe_card_token
     *
     * @return Response
     */
    public function associateCardAction($email_canonical, $stripe_card_token)
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $userService = $this->container->get('simplytestable.services.userservice');
        $userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');
        $stripeService = $this->container->get('simplytestable.services.stripeservice');

        if ($applicationStateService->isInMaintenanceReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        if ($applicationStateService->isInMaintenanceBackupReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        if ($userService->isPublicUser($this->getUser())) {
            return $this->sendFailureResponse();
        }

        if ($email_canonical !== $this->getUser()->getEmail()) {
            return $this->sendFailureResponse();
        }

        $isValidStripeCardToken = preg_match('/tok_[a-z0-9]{14}/i', $stripe_card_token) > 0;
        if (!$isValidStripeCardToken) {
            return $this->sendFailureResponse();
        }

        $userAccountPlan = $userAccountPlanService->getForUser($this->getUser());
        if (!$userAccountPlan->hasStripeCustomer()) {
            return $this->sendFailureResponse();
        }

        try {
            $stripeService->updateCustomer($userAccountPlan, [
                'card' => $stripe_card_token
            ]);
        } catch (StripeCardError $stripeCardError) {
            return $this->sendFailureResponse(array(
                'X-Stripe-Error-Message' => $stripeCardError->getMessage(),
                'X-Stripe-Error-Param' => $stripeCardError->param,
                'X-Stripe-Error-Code' => $stripeCardError->getCode()
            ));
        }

        return $this->sendSuccessResponse();
    }
}
