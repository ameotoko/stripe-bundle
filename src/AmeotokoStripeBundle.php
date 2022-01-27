<?php

declare(strict_types=1);

/**
 * @author Andrey Vinichenko <andrey.vinichenko@gmail.com>
 */

namespace Ameotoko\StripeBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class AmeotokoStripeBundle extends Bundle
{
    public function getContainerExtension()
    {
        if ($this->extension === null) {
            $this->extension = $this->createContainerExtension();
        }

        return $this->extension;
    }
}
