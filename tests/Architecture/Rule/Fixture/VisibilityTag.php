<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer {
    /**
     * @api
     *
     * @no-named-arguments
     */
    final class TaggedApi
    {
    }

    /**
     * @internal
     */
    final class InternalInRoot
    {
    }
}

namespace MathiasReker\PhpSvgOptimizer\Exception {
    /**
     * @no-named-arguments
     */
    final class UntaggedException
    {
    }
}

namespace MathiasReker\PhpSvgOptimizer\Pass {
    /**
     * @internal
     */
    final class TaggedPass
    {
    }

    /**
     * @api
     *
     * @no-named-arguments
     */
    final class ApiInPass
    {
    }

    /**
     * @no-named-arguments
     */
    trait UntaggedTrait
    {
    }

    /**
     * @no-named-arguments
     */
    interface UntaggedInterface
    {
    }
}

namespace MathiasReker\PhpSvgOptimizer\Tests {
    /**
     * @no-named-arguments
     */
    final class UntaggedTest
    {
    }
}

namespace Vendor {
    /**
     * @no-named-arguments
     */
    final class UntaggedVendor
    {
    }
}
