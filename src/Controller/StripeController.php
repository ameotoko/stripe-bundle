<?php

/**
 * @author Andrey Vinichenko <andrey.vinichenko@gmail.com>
 */

namespace Ameotoko\StripeBundle\Controller;

use Ameotoko\StripeBundle\Event\CreateCheckoutEvent;
use Ameotoko\StripeBundle\Event\WebhookEvent;
use Contao\CoreBundle\Monolog\ContaoContext;
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\UnexpectedValueException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class StripeController extends AbstractController
{
    private LoggerInterface $logger;

    public function __construct(string $stripeKey, LoggerInterface $logger)
    {
        Stripe::setApiKey($stripeKey);

        $this->logger = $logger;
    }

    /**
     * @Route(
     *     "/_stripe/payment",
     *     name="stripe_create_payment_intent",
     *     methods={"POST"},
     *     defaults={"_allow_preview": true}
     * )
     */
    public function createPaymentIntent(Request $request): JsonResponse
    {
        $paymentData = json_decode($request->getContent());

        $options = [
            'amount'   => $paymentData->amount,
            'currency' => $paymentData->currency,
            'description' => $paymentData->description
        ];

        try {
            $paymentIntent = PaymentIntent::create($options);

            return new JsonResponse(['clientSecret' => $paymentIntent->client_secret]);
        } catch (\Error $e) {
            $this->logger->error($e->getMessage(), ['contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)]);

            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @Route(
     *     "/_stripe/checkout",
     *     name="stripe_create_checkout_session",
     *     methods={"POST"},
     *     defaults={"_allow_preview": true}
     * )
     */
    public function createCheckoutSession(Request $request, EventDispatcherInterface $dispatcher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $event = new CreateCheckoutEvent($data);

        $dispatcher->dispatch($event, 'stripe.create_checkout.pre');

        try {
            $session = Session::create($event->params);
        } catch (ApiErrorException $e) {
            $this->logger->error($e->getMessage(), ['contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)]);

            return new JsonResponse(['error' => $e->getMessage()], 500);
        }

        $event = new CreateCheckoutEvent($session->toArray());

        $dispatcher->dispatch($event, 'stripe.create_checkout.post');

        return new JsonResponse(['url' => $session->url]);
    }

    /**
     * @Route("/_stripe/webhook", name="stripe_webhook", methods={"POST"})
     */
    public function webhook(Request $request, EventDispatcherInterface $dispatcher): Response
    {
        try {
            $event = Event::constructFrom(json_decode($request->getContent(), true));
        } catch (UnexpectedValueException $e) {
            return new Response('', 400);
        }

        $webhookEvent = new WebhookEvent($event);

        // Send HTTP 200 status before any complex logic
        $response = new Response();
        $response->sendHeaders();

        // allow listeners to subscribe to events like 'stripe.checkout.session.completed'
        $dispatcher->dispatch($webhookEvent, 'stripe.' . $event->type);

        return $response;
    }

    /**
     * This endpoint can be used in `success_url` or `cancel_url` parameter for Stripe's CheckoutSession,
     * if you open the session in new tab/window with `window.open()` instead of redirect.
     *
     * The purpose of the page is to close itself after redirecting back from the checkout page,
     * because reference to opened window is lost after navigating cross-domain,
     * and we don't want to leave behind duplicate tabs of the same website.
     *
     * Additionally, you can pass a `ref` and `result` parameters in a query string. The page then will send
     * the value of `result` parameter to the parent window using BroadcastChannel API, to which you can listen
     * in the parent window to e.g. show "Thank you" message.
     *
     * `ref` is a string and will be used as the channel identifier.
     * `result` can take one of 'success' or 'cancel' values.
     *
     * @Route("/_stripe/callback", name="stripe_callback", methods="GET")
     */
    public function callback(Request $request): Response
    {
        return $this->render('@AmeotokoStripe/callback.html.twig');
    }
}
