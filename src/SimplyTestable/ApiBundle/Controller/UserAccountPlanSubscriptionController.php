<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Exception\Services\UserAccountPlan\Exception as UserAccountPlanServiceException;
use SimplyTestable\ApiBundle\Services\AccountPlanService;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\UserService;
use Stripe\Error\Card as StripeCardError;
use Stripe\Error\Authentication as StripeAuthenticationError;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class UserAccountPlanSubscriptionController extends Controller
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
        $applicationStateService = $this->container->get(ApplicationStateService::class);
        $userService = $this->container->get(UserService::class);
        $userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');
        $accountPlanService = $this->container->get(AccountPlanService::class);

        if ($applicationStateService->isInReadOnlyMode()) {
            throw new ServiceUnavailableHttpException();
        }

        if ($userService->isPublicUser($this->getUser())) {
            return Response::create('', 400);
        }

        if ($email_canonical !== $this->getUser()->getEmail()) {
            return Response::create('', 400);
        }

        $plan = $accountPlanService->get($plan_name);

        if (empty($plan)) {
            return Response::create('', 400);
        }

        try {
            $userAccountPlanService->subscribe($this->getUser(), $plan);
        } catch (StripeAuthenticationError $stripeAuthenticationError) {
            return Response::create('', 403);
        } catch (StripeCardError $stripeCardError) {
            $userAccountPlanService->removeCurrentForUser($this->getUser());
            return Response::create('', 400, [
                'X-Stripe-Error-Message' => $stripeCardError->getMessage(),
                'X-Stripe-Error-Param' => $stripeCardError->param,
                'X-Stripe-Error-Code' => $stripeCardError->getCode()
            ]);
        } catch (UserAccountPlanServiceException $userAccountPlanServiceException) {
            if ($userAccountPlanServiceException->isUserIsTeamMemberException()) {
                return Response::create('', 400, [
                    'X-Error-Message' => 'User is a team member',
                    'X-Error-Code' => $userAccountPlanServiceException->getCode()
                ]);
            }
        }

        return new Response();
    }

    /**
     * @param string $email_canonical
     * @param string $stripe_card_token
     *
     * @return Response
     */
    public function associateCardAction($email_canonical, $stripe_card_token)
    {
        $applicationStateService = $this->container->get(ApplicationStateService::class);
        $userService = $this->container->get(UserService::class);
        $userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');
        $stripeService = $this->container->get('simplytestable.services.stripeservice');

        if ($applicationStateService->isInReadOnlyMode()) {
            throw new ServiceUnavailableHttpException();
        }

        if ($userService->isPublicUser($this->getUser())) {
            return Response::create('', 400);
        }

        if ($email_canonical !== $this->getUser()->getEmail()) {
            return Response::create('', 400);
        }

        $isValidStripeCardToken = preg_match('/tok_[a-z0-9]{14}/i', $stripe_card_token) > 0;
        if (!$isValidStripeCardToken) {
            return Response::create('', 400);
        }

        $userAccountPlan = $userAccountPlanService->getForUser($this->getUser());

        if (empty($userAccountPlan->getStripeCustomer())) {
            return Response::create('', 400);
        }

        try {
            $stripeService->updateCustomer($userAccountPlan, [
                'card' => $stripe_card_token
            ]);
        } catch (StripeCardError $stripeCardError) {
            return Response::create('', 400, [
                'X-Stripe-Error-Message' => $stripeCardError->getMessage(),
                'X-Stripe-Error-Param' => $stripeCardError->param,
                'X-Stripe-Error-Code' => $stripeCardError->getCode()
            ]);
        }

        return new Response();
    }
}
