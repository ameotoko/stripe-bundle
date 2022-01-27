<?php

/**
 * @author Andrey Vinichenko <andrey.vinichenko@gmail.com>
 */

namespace Ameotoko\StripeBundle\Event;

use Stripe\Event as StripeEvent;
use Symfony\Contracts\EventDispatcher\Event;

class WebhookEvent extends Event
{
    public array $data;
    public string $type;

    public function __construct(StripeEvent $event)
    {
        $this->data = $event->data->toArray();
        $this->type = $event->type;
    }
}
