<?php

/**
 * This code was generated by
 * \ / _    _  _|   _  _
 * | (_)\/(_)(_|\/| |(/_  v1.0.0
 * /       /
 */
namespace WSAL_Vendor\Twilio\Rest\Api\V2010\Account\AvailablePhoneNumberCountry;

use WSAL_Vendor\Twilio\ListResource;
use WSAL_Vendor\Twilio\Options;
use WSAL_Vendor\Twilio\Serialize;
use WSAL_Vendor\Twilio\Stream;
use WSAL_Vendor\Twilio\Values;
use WSAL_Vendor\Twilio\Version;
class LocalList extends \WSAL_Vendor\Twilio\ListResource
{
    /**
     * Construct the LocalList
     *
     * @param Version $version Version that contains the resource
     * @param string $accountSid The account_sid
     * @param string $countryCode The ISO-3166-1 country code of the country.
     */
    public function __construct(\WSAL_Vendor\Twilio\Version $version, string $accountSid, string $countryCode)
    {
        parent::__construct($version);
        // Path Solution
        $this->solution = ['accountSid' => $accountSid, 'countryCode' => $countryCode];
        $this->uri = '/Accounts/' . \rawurlencode($accountSid) . '/AvailablePhoneNumbers/' . \rawurlencode($countryCode) . '/Local.json';
    }
    /**
     * Streams LocalInstance records from the API as a generator stream.
     * This operation lazily loads records as efficiently as possible until the
     * limit
     * is reached.
     * The results are returned as a generator, so this operation is memory
     * efficient.
     *
     * @param array|Options $options Optional Arguments
     * @param int $limit Upper limit for the number of records to return. stream()
     *                   guarantees to never return more than limit.  Default is no
     *                   limit
     * @param mixed $pageSize Number of records to fetch per request, when not set
     *                        will use the default value of 50 records.  If no
     *                        page_size is defined but a limit is defined, stream()
     *                        will attempt to read the limit with the most
     *                        efficient page size, i.e. min(limit, 1000)
     * @return Stream stream of results
     */
    public function stream(array $options = [], int $limit = null, $pageSize = null) : \WSAL_Vendor\Twilio\Stream
    {
        $limits = $this->version->readLimits($limit, $pageSize);
        $page = $this->page($options, $limits['pageSize']);
        return $this->version->stream($page, $limits['limit'], $limits['pageLimit']);
    }
    /**
     * Reads LocalInstance records from the API as a list.
     * Unlike stream(), this operation is eager and will load `limit` records into
     * memory before returning.
     *
     * @param array|Options $options Optional Arguments
     * @param int $limit Upper limit for the number of records to return. read()
     *                   guarantees to never return more than limit.  Default is no
     *                   limit
     * @param mixed $pageSize Number of records to fetch per request, when not set
     *                        will use the default value of 50 records.  If no
     *                        page_size is defined but a limit is defined, read()
     *                        will attempt to read the limit with the most
     *                        efficient page size, i.e. min(limit, 1000)
     * @return LocalInstance[] Array of results
     */
    public function read(array $options = [], int $limit = null, $pageSize = null) : array
    {
        return \iterator_to_array($this->stream($options, $limit, $pageSize), \false);
    }
    /**
     * Retrieve a single page of LocalInstance records from the API.
     * Request is executed immediately
     *
     * @param array|Options $options Optional Arguments
     * @param mixed $pageSize Number of records to return, defaults to 50
     * @param string $pageToken PageToken provided by the API
     * @param mixed $pageNumber Page Number, this value is simply for client state
     * @return LocalPage Page of LocalInstance
     */
    public function page(array $options = [], $pageSize = \WSAL_Vendor\Twilio\Values::NONE, string $pageToken = \WSAL_Vendor\Twilio\Values::NONE, $pageNumber = \WSAL_Vendor\Twilio\Values::NONE) : \WSAL_Vendor\Twilio\Rest\Api\V2010\Account\AvailablePhoneNumberCountry\LocalPage
    {
        $options = new \WSAL_Vendor\Twilio\Values($options);
        $params = \WSAL_Vendor\Twilio\Values::of(['AreaCode' => $options['areaCode'], 'Contains' => $options['contains'], 'SmsEnabled' => \WSAL_Vendor\Twilio\Serialize::booleanToString($options['smsEnabled']), 'MmsEnabled' => \WSAL_Vendor\Twilio\Serialize::booleanToString($options['mmsEnabled']), 'VoiceEnabled' => \WSAL_Vendor\Twilio\Serialize::booleanToString($options['voiceEnabled']), 'ExcludeAllAddressRequired' => \WSAL_Vendor\Twilio\Serialize::booleanToString($options['excludeAllAddressRequired']), 'ExcludeLocalAddressRequired' => \WSAL_Vendor\Twilio\Serialize::booleanToString($options['excludeLocalAddressRequired']), 'ExcludeForeignAddressRequired' => \WSAL_Vendor\Twilio\Serialize::booleanToString($options['excludeForeignAddressRequired']), 'Beta' => \WSAL_Vendor\Twilio\Serialize::booleanToString($options['beta']), 'NearNumber' => $options['nearNumber'], 'NearLatLong' => $options['nearLatLong'], 'Distance' => $options['distance'], 'InPostalCode' => $options['inPostalCode'], 'InRegion' => $options['inRegion'], 'InRateCenter' => $options['inRateCenter'], 'InLata' => $options['inLata'], 'InLocality' => $options['inLocality'], 'FaxEnabled' => \WSAL_Vendor\Twilio\Serialize::booleanToString($options['faxEnabled']), 'PageToken' => $pageToken, 'Page' => $pageNumber, 'PageSize' => $pageSize]);
        $response = $this->version->page('GET', $this->uri, $params);
        return new \WSAL_Vendor\Twilio\Rest\Api\V2010\Account\AvailablePhoneNumberCountry\LocalPage($this->version, $response, $this->solution);
    }
    /**
     * Retrieve a specific page of LocalInstance records from the API.
     * Request is executed immediately
     *
     * @param string $targetUrl API-generated URL for the requested results page
     * @return LocalPage Page of LocalInstance
     */
    public function getPage(string $targetUrl) : \WSAL_Vendor\Twilio\Rest\Api\V2010\Account\AvailablePhoneNumberCountry\LocalPage
    {
        $response = $this->version->getDomain()->getClient()->request('GET', $targetUrl);
        return new \WSAL_Vendor\Twilio\Rest\Api\V2010\Account\AvailablePhoneNumberCountry\LocalPage($this->version, $response, $this->solution);
    }
    /**
     * Provide a friendly representation
     *
     * @return string Machine friendly representation
     */
    public function __toString() : string
    {
        return '[Twilio.Api.V2010.LocalList]';
    }
}
