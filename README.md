# Simple Stripe bundle for Contao 4

The bundle sets up basic environment, if you want to start using [Stripe][2] in your Contao application.

## API key parameters

Add your API keys to the bundle config, and they will also become available as container parameters:

```yaml
# config/config.yml

stripe:
    secret_key: 'sk_test_****'
    publishable_key: 'pk_test_****'
```

Then use it in your own services if you need:

```yaml
# config/services.yml

services:
    App\EventListener\StripeListener:
        arguments:
            - '%stripe.secret_key%'
```

IMPORTANT: store your production API keys in environment variables, to avoid committing them to version control:

```dotenv
# .env.local

STRIPE_SECRET_KEY=sk_live_****
```

```yaml
# config/config_prod.yml

stripe:
    secret_key: '%env(STRIPE_SECRET_KEY)%'
```

## Endpoints

| Endpoint            | Route                            |
|---------------------|----------------------------------|
| `/_stripe/payment`  | `stripe_create_payment_intent`   |
| `/_stripe/checkout` | `stripe_create_checkout_session` |
| `/_stripe/webhook`  | `stripe_webhook`                 |


The bundle creates 2 endpoints in your application, which you can call from your Javascript to create payment intents or checkout sessions:

```php
<?php // my-template.html5 ?>

<script src="https://js.stripe.com/v3/"></script>
<script>
    const stripe = Stripe('<?= \Contao\System::getContainer()->getParameter('stripe.publishable_key') ?>');

    const paymentData = {
        success_url: '...',
        cancel_url: '...',
        payment_method_types: ['card', 'sepa_debit', 'sofort', 'ideal', 'alipay'],
        mode: 'payment',
        billing_address_collection: 'required',
        line_items: [...],
        metadata: {...}
    }

    fetch('<?= $this->route('stripe_create_checkout_session') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(paymentData)
    })
    .then(response => response.json())
    .then(session => stripe.redirectToCheckout({sessionId: session.id}))
</script>
```

The bundle also sets up a webhook endpoint `/_stripe/webhook`, which you can configure in your Stripe account, e.g. `https://example.com/_stripe/webhook`.

Then, you can process any events, that Stripe sends to your webhook endpoint, by setting up an `EventListener`, that listens to events like 'stripe.' + Stripe event name. For example, if you want to process Stripe's `checkout.session.completed`:

```yaml
# config/services.yml

services:
    App\EventListener\StripeCheckoutSessionCompleted:
        tags:
            - { name: kernel.event_listener, event: stripe.checkout.session.completed }
```

## Events

- `stripe.create_checkout.pre` (receives data object you've sent from your frontend, before sending it to Stripe)
- `stripe.create_checkout.post` (receives the response from `Stripe\Checkout\Session::create()`)
- `stripe.<STRIPE_WEBHOOK_EVENT>`

---

Contao is an Open Source PHP Content Management System for people who want a
professional website that is easy to maintain. Visit the [project website][1]
for more information.

[1]: https://contao.org
[2]: https://stripe.com
