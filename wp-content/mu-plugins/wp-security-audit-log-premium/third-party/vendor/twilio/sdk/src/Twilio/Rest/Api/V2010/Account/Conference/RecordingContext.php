<?php

/**
 * This code was generated by
 * \ / _    _  _|   _  _
 * | (_)\/(_)(_|\/| |(/_  v1.0.0
 * /       /
 */
namespace WSAL_Vendor\Twilio\Rest\Api\V2010\Account\Conference;

use WSAL_Vendor\Twilio\Exceptions\TwilioException;
use WSAL_Vendor\Twilio\InstanceContext;
use WSAL_Vendor\Twilio\Options;
use WSAL_Vendor\Twilio\Values;
use WSAL_Vendor\Twilio\Version;
class RecordingContext extends \WSAL_Vendor\Twilio\InstanceContext
{
    /**
     * Initialize the RecordingContext
     *
     * @param Version $version Version that contains the resource
     * @param string $accountSid The SID of the Account that created the resource
     *                           to fetch
     * @param string $conferenceSid Fetch by unique Conference SID for the recording
     * @param string $sid The unique string that identifies the resource
     */
    public function __construct(\WSAL_Vendor\Twilio\Version $version, $accountSid, $conferenceSid, $sid)
    {
        parent::__construct($version);
        // Path Solution
        $this->solution = ['accountSid' => $accountSid, 'conferenceSid' => $conferenceSid, 'sid' => $sid];
        $this->uri = '/Accounts/' . \rawurlencode($accountSid) . '/Conferences/' . \rawurlencode($conferenceSid) . '/Recordings/' . \rawurlencode($sid) . '.json';
    }
    /**
     * Update the RecordingInstance
     *
     * @param string $status The new status of the recording
     * @param array|Options $options Optional Arguments
     * @return RecordingInstance Updated RecordingInstance
     * @throws TwilioException When an HTTP error occurs.
     */
    public function update(string $status, array $options = []) : \WSAL_Vendor\Twilio\Rest\Api\V2010\Account\Conference\RecordingInstance
    {
        $options = new \WSAL_Vendor\Twilio\Values($options);
        $data = \WSAL_Vendor\Twilio\Values::of(['Status' => $status, 'PauseBehavior' => $options['pauseBehavior']]);
        $payload = $this->version->update('POST', $this->uri, [], $data);
        return new \WSAL_Vendor\Twilio\Rest\Api\V2010\Account\Conference\RecordingInstance($this->version, $payload, $this->solution['accountSid'], $this->solution['conferenceSid'], $this->solution['sid']);
    }
    /**
     * Fetch the RecordingInstance
     *
     * @return RecordingInstance Fetched RecordingInstance
     * @throws TwilioException When an HTTP error occurs.
     */
    public function fetch() : \WSAL_Vendor\Twilio\Rest\Api\V2010\Account\Conference\RecordingInstance
    {
        $payload = $this->version->fetch('GET', $this->uri);
        return new \WSAL_Vendor\Twilio\Rest\Api\V2010\Account\Conference\RecordingInstance($this->version, $payload, $this->solution['accountSid'], $this->solution['conferenceSid'], $this->solution['sid']);
    }
    /**
     * Delete the RecordingInstance
     *
     * @return bool True if delete succeeds, false otherwise
     * @throws TwilioException When an HTTP error occurs.
     */
    public function delete() : bool
    {
        return $this->version->delete('DELETE', $this->uri);
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
        return '[Twilio.Api.V2010.RecordingContext ' . \implode(' ', $context) . ']';
    }
}