<?php

/**
 * This code was generated by
 * \ / _    _  _|   _  _
 * | (_)\/(_)(_|\/| |(/_  v1.0.0
 * /       /
 */
namespace WSAL_Vendor\Twilio\Rest\Api\V2010\Account\AvailablePhoneNumberCountry;

use WSAL_Vendor\Twilio\Exceptions\TwilioException;
use WSAL_Vendor\Twilio\InstanceResource;
use WSAL_Vendor\Twilio\Values;
use WSAL_Vendor\Twilio\Version;
/**
 * @property string $friendlyName
 * @property string $phoneNumber
 * @property string $lata
 * @property string $locality
 * @property string $rateCenter
 * @property string $latitude
 * @property string $longitude
 * @property string $region
 * @property string $postalCode
 * @property string $isoCountry
 * @property string $addressRequirements
 * @property bool $beta
 * @property string $capabilities
 */
class SharedCostInstance extends \WSAL_Vendor\Twilio\InstanceResource
{
    /**
     * Initialize the SharedCostInstance
     *
     * @param Version $version Version that contains the resource
     * @param mixed[] $payload The response payload
     * @param string $accountSid The account_sid
     * @param string $countryCode The ISO-3166-1 country code of the country.
     */
    public function __construct(\WSAL_Vendor\Twilio\Version $version, array $payload, string $accountSid, string $countryCode)
    {
        parent::__construct($version);
        // Marshaled Properties
        $this->properties = ['friendlyName' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'friendly_name'), 'phoneNumber' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'phone_number'), 'lata' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'lata'), 'locality' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'locality'), 'rateCenter' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'rate_center'), 'latitude' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'latitude'), 'longitude' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'longitude'), 'region' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'region'), 'postalCode' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'postal_code'), 'isoCountry' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'iso_country'), 'addressRequirements' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'address_requirements'), 'beta' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'beta'), 'capabilities' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'capabilities')];
        $this->solution = ['accountSid' => $accountSid, 'countryCode' => $countryCode];
    }
    /**
     * Magic getter to access properties
     *
     * @param string $name Property to access
     * @return mixed The requested property
     * @throws TwilioException For unknown properties
     */
    public function __get(string $name)
    {
        if (\array_key_exists($name, $this->properties)) {
            return $this->properties[$name];
        }
        if (\property_exists($this, '_' . $name)) {
            $method = 'get' . \ucfirst($name);
            return $this->{$method}();
        }
        throw new \WSAL_Vendor\Twilio\Exceptions\TwilioException('Unknown property: ' . $name);
    }
    /**
     * Provide a friendly representation
     *
     * @return string Machine friendly representation
     */
    public function __toString() : string
    {
        return '[Twilio.Api.V2010.SharedCostInstance]';
    }
}