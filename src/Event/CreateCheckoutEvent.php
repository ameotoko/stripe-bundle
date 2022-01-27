<?php

/**
 * @author Andrey Vinichenko <andrey.vinichenko@gmail.com>
 */

namespace Ameotoko\StripeBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class CreateCheckoutEvent extends Event
{
    public array $params;

    /**
     * @param array $params parameters that will be used by Stripe\Session::create
     */
    public function __construct(array $params = [])
    {
        // make sure metadata is always an array
        if (!\is_array($params['metadata'])) {
            $params['metadata'] = [];
        }

        $this->params = $params;
    }
}
