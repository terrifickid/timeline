<?php

/**
 * This code was generated by
 * \ / _    _  _|   _  _
 * | (_)\/(_)(_|\/| |(/_  v1.0.0
 * /       /
 */
namespace WSAL_Vendor\Twilio\Rest\Api\V2010\Account;

use WSAL_Vendor\Twilio\Exceptions\TwilioException;
use WSAL_Vendor\Twilio\InstanceResource;
use WSAL_Vendor\Twilio\Options;
use WSAL_Vendor\Twilio\Values;
use WSAL_Vendor\Twilio\Version;
/**
 * @property string $accountSid
 * @property string $authorizeRedirectUrl
 * @property string $companyName
 * @property string $deauthorizeCallbackMethod
 * @property string $deauthorizeCallbackUrl
 * @property string $description
 * @property string $friendlyName
 * @property string $homepageUrl
 * @property string[] $permissions
 * @property string $sid
 * @property string $uri
 */
class ConnectAppInstance extends \WSAL_Vendor\Twilio\InstanceResource
{
    /**
     * Initialize the ConnectAppInstance
     *
     * @param Version $version Version that contains the resource
     * @param mixed[] $payload The response payload
     * @param string $accountSid The SID of the Account that created the resource
     * @param string $sid The unique string that identifies the resource
     */
    public function __construct(\WSAL_Vendor\Twilio\Version $version, array $payload, string $accountSid, string $sid = null)
    {
        parent::__construct($version);
        // Marshaled Properties
        $this->properties = ['accountSid' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'account_sid'), 'authorizeRedirectUrl' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'authorize_redirect_url'), 'companyName' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'company_name'), 'deauthorizeCallbackMethod' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'deauthorize_callback_method'), 'deauthorizeCallbackUrl' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'deauthorize_callback_url'), 'description' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'description'), 'friendlyName' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'friendly_name'), 'homepageUrl' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'homepage_url'), 'permissions' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'permissions'), 'sid' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'sid'), 'uri' => \WSAL_Vendor\Twilio\Values::array_get($payload, 'uri')];
        $this->solution = ['accountSid' => $accountSid, 'sid' => $sid ?: $this->properties['sid']];
    }
    /**
     * Generate an instance context for the instance, the context is capable of
     * performing various actions.  All instance actions are proxied to the context
     *
     * @return ConnectAppContext Context for this ConnectAppInstance
     */
    protected function proxy() : \WSAL_Vendor\Twilio\Rest\Api\V2010\Account\ConnectAppContext
    {
        if (!$this->context) {
            $this->context = new \WSAL_Vendor\Twilio\Rest\Api\V2010\Account\ConnectAppContext($this->version, $this->solution['accountSid'], $this->solution['sid']);
        }
        return $this->context;
    }
    /**
     * Fetch the ConnectAppInstance
     *
     * @return ConnectAppInstance Fetched ConnectAppInstance
     * @throws TwilioException When an HTTP error occurs.
     */
    public function fetch() : \WSAL_Vendor\Twilio\Rest\Api\V2010\Account\ConnectAppInstance
    {
        return $this->proxy()->fetch();
    }
    /**
     * Update the ConnectAppInstance
     *
     * @param array|Options $options Optional Arguments
     * @return ConnectAppInstance Updated ConnectAppInstance
     * @throws TwilioException When an HTTP error occurs.
     */
    public function update(array $options = []) : \WSAL_Vendor\Twilio\Rest\Api\V2010\Account\ConnectAppInstance
    {
        return $this->proxy()->update($options);
    }
    /**
     * Delete the ConnectAppInstance
     *
     * @return bool True if delete succeeds, false otherwise
     * @throws TwilioException When an HTTP error occurs.
     */
    public function delete() : bool
    {
        return $this->proxy()->delete();
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
        $context = [];
        foreach ($this->solution as $key => $value) {
            $context[] = "{$key}={$value}";
        }
        return '[Twilio.Api.V2010.ConnectAppInstance ' . \implode(' ', $context) . ']';
    }
}
