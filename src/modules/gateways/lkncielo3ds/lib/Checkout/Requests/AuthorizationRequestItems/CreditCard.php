<?php

namespace WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\AuthorizationRequestItems;

/**
 * @since 1.0.0
 */
final class CreditCard
{
    /**
     * Is set later in AuthorizationService by bin query.
     *
     * @since 1.0.0
     * @var string
     */
    public readonly string $brand;

    /**
     * The card type according to the Cielo bin query.
     * Is set later in AuthorizationService by bin query.
     *
     * @since 1.0.0
     * @var string
     */
    public readonly string $binType;

    /**
     * @since 1.0.0
     *
     * @param string $number
     * @param string $holder
     * @param string $expirationDate
     * @param bool   $saveCard
     * @param string $type           "debit" or "credit".
     */
    public function __construct(
        public readonly string $number,
        public readonly string $holder,
        public readonly string $expirationDate,
        public readonly bool $saveCard,
        public readonly string $type,
        public readonly string $cvv
    ) {
        //
    }

    public function setBrand(string $brand): void
    {
        $this->brand = $brand;
    }

    public function setBinType(string $binType): void
    {
        $this->binType = $binType;
    }
}
