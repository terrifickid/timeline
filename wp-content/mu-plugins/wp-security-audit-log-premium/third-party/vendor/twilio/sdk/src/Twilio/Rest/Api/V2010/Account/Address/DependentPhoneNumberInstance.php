<?php

/**
 * This code was generated by
 * \ / _    _  _|   _  _
 * | (_)\/(_)(_|\/| |(/_  v1.0.0
 * /       /
 */
namespace WSAL_Vendor\Twilio\Rest\Api\V2010\Account\Address;

use WSAL_Vendor\Twilio\Deserialize;
use WSAL_Vendor\Twilio\Exceptions\TwilioException;
use WSAL_Vendor\Twilio\InstanceResource;
use WSAL_Vendor\Twilio\Values;
use WSAL_Vendor\Twilio\Version;
/**
 * @property string $sid
 * @property string $accountSid
 * @property string $friendlyName
 * @property string $phoneNumber
 * @property string $voiceUrl
 * @property string $voiceMethod
 * @property string $voiceFallbackMethod
 * @property string $voiceFallbackUrl
 * @property bool $voiceCallerIdLookup
 * @property \DateTime $dateCreated
 * @property \DateTime $dateUpdated
 * @property string $smsFallbackMethod
 * @property string $smsFallbackUrl
 * @property string $smsMethod
 * @property string $smsUrl
 * @property string $addressRequirements
 * @property array $capabilities
 * @property string $statusCallback
 * @property string $statusCallbackMethod
 * @property string $apiVersion
 * @property string $smsApplicationSid
 * @property string $voiceApplicationSid
 * @property string $trunkSid
 * @property string $emergencyStatus
 * @property string $emergencyAddressSid
 * @property string $uri
 */
class DependentPhoneNumberInstance extends \WSAL_Vendor\Twilio\InstanceResource
{
    /**
     * Initialize the DependentPhoneNumberInstance
     *
     * @param Version $version Version that contains the resource
     * @param mixed[] $payload The response payload
     * @param string $accountSid The SID of the Account that created the resource
     * @param string $addressSid The unique string that identifies the resource
     */
    public function __construct(\WSAL_Vendor\Twilio\Version $version, array $payload, string $accountSid, string $addressSid)
    {
        parent::__construct($version);
        // Marshaled Properties
        $this->properties = ['sid' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'sid'), 'accountSid' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'account_sid'), 'friendlyName' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'friendly_name'), 'phoneNumber' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'phone_number'), 'voiceUrl' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'voice_url'), 'voiceMethod' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'voice_method'), 'voiceFallbackMethod' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'voice_fallback_method'), 'voiceFallbackUrl' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'voice_fallback_url'), 'voiceCallerIdLookup' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'voice_caller_id_lookup'), 'dateCreated' => \WSAL_Vendor\Twilio\Deserialize::dateTime(\WSAL_Vendor\Twilio\Values::array_get($payload, 'date_created')), 'dateUpdated' => \WSAL_Vendor\Twilio\Deserialize::dateTime(\WSAL_Vendor\Twilio\Values::array_get($payload, 'date_updated')), 'smsFallbackMethod' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'sms_fallback_method'), 'smsFallbackUrl' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'sms_fallback_url'), 'smsMethod' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'sms_method'), 'smsUrl' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'sms_url'), 'addressRequirements' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'address_requirements'), 'capabilities' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'capabilities'), 'statusCallback' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'status_callback'), 'statusCallbackMethod' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'status_callback_method'), 'apiVersion' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'api_version'), 'smsApplicationSid' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'sms_application_sid'), 'voiceApplicationSid' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'voice_application_sid'), 'trunkSid' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'trunk_sid'), 'emergencyStatus' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'emergency_status'), 'emergencyAddressSid' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'emergency_address_sid'), 'uri' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'uri')];
        $this->solution = ['accountSid' => $accountSid, 'addressSid' => $addressSid];
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
        return '[Twilio.Api.V2010.DependentPhoneNumberInstance]';
    }
}