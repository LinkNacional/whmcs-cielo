<?php

namespace WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\ClassMapFormItems;

final class Address
{
    public readonly string $shipToName;
    public readonly string $shipToPhoneNumber;
    public readonly string $shipToEmail;
    public readonly string $shipToStreet1;
    public readonly string $shipToStree2;
    public readonly string $shipToCity;
    public readonly string $shipToState;
    public readonly string $shipToCountry;
    public readonly string $shipToZipCode;
    public readonly string $shipToShippingMethod;
    public readonly string $shipToLastUsageDate;

    public function __construct(
        public readonly string $customerId,
        public readonly string $newCustomer,
        public readonly string $billToName,
        public readonly string $phoneNumber,
        public readonly string $email,
        public readonly string $street1,
        public readonly string $street2,
        public readonly string $number,
        public readonly string $city,
        public readonly string $state,
        public readonly string $country,
        public readonly string $zipcode,
        public readonly bool $isDeliveryAddressSameAsBilling
    ) {
        //
    }

    public function setDeliveryAddress(
        string $shipToName,
        string $shipToPhoneNumber,
        string $shipToEmail,
        string $shipToStreet1,
        string $shipToStree2,
        string $shipToCity,
        string $shipToState,
        string $shipToCountry,
        string $shipToZipCode,
        string $shipToShippingMethod,
        string $shipToLastUsageDate
    ): void {
        $this->shipToName = $shipToName;
        $this->shipToPhoneNumber = $shipToPhoneNumber;
        $this->shipToEmail = $shipToEmail;
        $this->shipToStreet1 = $shipToStreet1;
        $this->shipToStree2 = $shipToStree2;
        $this->shipToCity = $shipToCity;
        $this->shipToState = $shipToState;
        $this->shipToCountry = $shipToCountry;
        $this->shipToZipCode = $shipToZipCode;
        $this->shipToShippingMethod = $shipToShippingMethod;
        $this->shipToLastUsageDate = $shipToLastUsageDate;
    }
}
