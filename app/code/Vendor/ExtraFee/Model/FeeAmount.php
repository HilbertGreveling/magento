<?php
declare(strict_types=1);

namespace Vendor\ExtraFee\Model;

/**
 * Fee amount in both store and base currency.
 */
class FeeAmount
{
    /**
     * @param float $amount
     * @param float $baseAmount
     */
    public function __construct(
        public readonly float $amount,
        public readonly float $baseAmount
    ) {
    }
}
