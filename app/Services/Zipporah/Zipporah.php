<?php

namespace Ceremonies\Services\Zipporah;

use Ceremonies\Services\Logger;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;

class Zipporah
{

    /**
     * The sandpit/sandbox API URL.
     * @var string
     */
    private string $sandpit_uri = 'WebAPI.Staffordshire.Sandpit';

    /**
     * The production API URL.
     * @var string
     */
    private string $live_uri = 'WebAPI.Staffordshire';

    /**
     * The base URI for the API.
     * @var string
     */
    private string $base_uri = 'https://staffordshire.zipporah.co.uk/';

    /**
     * The current API token.
     * @var string
     */
    private string $token = '';

	private bool $useDevMode = true;

    /**
     * The GuzzleHttp Client instance.
     * @var Client
     */
    private Client $client;

    /**
     * The handler stack for any Guzzle middleware.
     * @var HandlerStack
     */
    private $stack;

    /**
     * Setup zipporah API instance
     * TODO: pull through api creds/existing token
     */
    public function __construct()
    {
        // Setup Guzzle middleware handler
        $handler = new CurlHandler();
        $this->stack = HandlerStack::create($handler);

        // Setup Guzzle
        $this->client = new Client([
            'base_uri' => $this->base_uri,
            'handler' => $this->stack,
            'defaults' => [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ]
            ]
        ]);
    }

	/**
	 * Gets the current token, either from the current
	 * object or a transient.
	 *
	 * @return false|string
	 */
	private function getToken() {

		if ($this->token) {
			return $this->token;
		}

		$transient = get_transient('sc-zip-token');
		if ($transient) {
			$this->token = $transient;
			return $this->token;
		}

		return false;

	}

	/**
	 * Set token as a transient and on the current
	 * object. Tokens expire fast, but it is not clear how
	 * fast.
	 *
	 * @param $token
	 * @return void
	 */
	private function setToken($token) {
		set_transient('sc-zip-token', $token, HOUR_IN_SECONDS);
		$this->token = $token;
	}

    /**
     * Gets an access token from Zipporah
     *
     * @return void
     * @throws \Exception
     */
    private function fetchToken()
    {

        $url = $this->getUri() . '/GenerateToken';

        // Get the token
        $response = $this->client->post($url, [
            'headers' => $this->getHeaders(),
        ]);

	    Logger::log('Zipporah request to: ' . $this->base_uri . $url . ' --- Response: ' . $response->getBody());

        // Throw an exception if we have the wrong creds
        if ((string) $response->getBody() === '"No Token Generated. Invalid credentials supplied"'){
            throw new \Exception('Invalid API username/password - Token not generated');
        }

        $this->setToken(json_decode($response->getBody()));

    }

    private function getCachedResponse($url, $params)
    {
        $transientKey = $this->getTransientKey($url, $params);
        return get_transient($transientKey);
    }

    private function cacheResponse($url, $params, $data)
    {
        $transientKey = $this->getTransientKey($url, $params);
        set_transient($transientKey, $data, 15 * MINUTE_IN_SECONDS);
    }

    private function getTransientKey($url, $params)
    {
        return 'sc-' . md5($url . json_encode($params));
    }

	/**
	 * Gets the appropriate URI depending on
	 * $useDevMode.
	 *
	 * @return string
	 */
	private function getUri(): string {
		return $this->useDevMode ? $this->sandpit_uri : $this->live_uri;
	}

	/**
	 * Gets the appropriate auth headers depending on
	 * $useDevMode.
	 *
	 * @return string[]
	 */
	private function getHeaders(): array {
		if ($this->useDevMode) {
			return [
				'SystemUsername' => 'WeThrive',
				'SystemPassword' => 'Password1'
			];
		}
		return [
			'SystemUsername' => 'WeThrive',
			'SystemPassword' => 'Staffordshire1'
		];
	}

    /**
     * Handles making requests directly to Zipporah's API.
     *
     * @param $url
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    private function makeRequest($url, $data = [])
    {

        $url = $this->getUri() . $url;

        Logger::log('✨ Starting request to: ' . $url . ' with: ' . json_encode($data));

        // TODO: Implement caching with transients
//        $response = $this->getCachedResponse($url, $data);
//        if ($response) {
//            return $response;
//        }

        // Setup middleware to log requests
        $tap = $this->setupTapMiddlware();

        try {

            // Make request to Zipporah
            $response = $this->client->post($url, [
                'json' => $data,
                'handler' => $tap($this->stack),
                'headers' => [
                    'UserToken' => $this->getToken(),
                ]
            ]);

        } catch(ServerException $e) {

            // If the request fails, check if it's down to and expired token
            $response = json_decode($e->getResponse()->getBody());
            if ($response->ExceptionMessage === 'Invalid Or No Token supplied in header.'){
                // If token is expired, regenerate and repeat request.
	            // Handled in catch as repeat should only be performed once.
                $response = $this->repeatRequest($url, $data);
            }

        }

		// Log direct response
//	    Logger::log('Zipporah response to: ' . $url . ' ' . json_encode($response));
//	    print('<pre>'.print_r(json_encode($data, JSON_PRETTY_PRINT), true).'</pre>');
//	    print('<pre>'.print_r($response, true).'</pre>');
	    Logger::log('Zipporah response to: ' . $url . ' ' . json_encode($response->getHeaders()));
	    Logger::log('Zipporah response to: ' . $url . ' ' . json_encode($response->getBody()));

		// Decode response and cache.
        $responseData = json_decode($response->getBody());
        $this->cacheResponse($url, $data, $responseData);

        // Convert to json and return
        return $responseData;

    }

	private function repeatRequest($url, $data) {
		$this->fetchToken();
		return $this->client->request('POST', $url, [
			'json' => $data,
			'headers' => [
				'UserToken' => $this->getToken(),
			]
		]);
	}

    /**
     * Watches any requests sent and logs the data for them.
     *
     * @return callable|\Closure
     */
    private function setupTapMiddlware(): callable|\Closure
    {
        // Create a middleware that echoes parts of the request.
       return Middleware::tap(function ($request) {
           Logger::log('Zipporah request to: ' . $request->getUri()->getPath() . ' ' . $request->getBody() . ' --- Used token: ' . $this->token);
        });
    }

    /**
     * Gets the booking types from Zipporah.
     *
     * @return mixed
     * @throws \Exception
     */
    public function listBookingTypes(): mixed
    {
        return $this->makeRequest('/ListBookingTypes');
    }

    /**
     * List all venues available for a Registrar at an approved
     * venue.
     *
     * @return mixed
     * @throws \Exception
     */
    public function listVenues($type)
    {
        $data = $this->getBookingType($type);
        return $this->makeRequest('/ListResourcesForBookingType', $data);
    }

    /**
     * Find the availability of a registrar at an approved
     * venue.
     *
     * @param $bookingTypeId
     * @param $resourceId
     * @param $start
     * @param $end
     * @return mixed
     * @throws \Exception
     */
    public function searchAvailability($bookingTypeId, $resourceId, $start)
    {

        /**
         * Dates have to be sent in the American format, but are
         * returned in UK format.
         * Send -> 11/01/2024
         * Returns -> 01/11/2024
         */
        $slots = $this->makeRequest('/ListAvailability', [
            'BookingTypeId' => $bookingTypeId,
            'ResourceId' => $resourceId,
            'StartDateTime' => $start,
            'EndDateTime' => $this->getEndDateFromStart($start),
        ]);
        return $this->filterAvailability($slots);
    }

    public function getCountyBuildingsAvailability($date)
    {
        $venues = $this->listVenues('county');
        $venues = $this->filterOvVenues($venues, 'Stafford');

        $slots = [];
        foreach ($venues as $venue) {
            $results = $this->searchAvailability(1455456, $venue->ResourceId, $date);
            foreach ($results as $result_date => $times) {
                foreach ($times as $time_slot) {
                    $slots[$result_date][$time_slot->AvailableStartTime] = $time_slot;
                }
            }
        }

        $slots = $this->sortAvailability($slots);

        return $slots;

    }


	public function getVenueAvailability($bookingType, $venues, $date) {

		$slots = [];
		foreach ($venues as $venue) {
			$results = $this->searchAvailability(1455456, $venue->ResourceId, $date);
			foreach ($results as $result_date => $times) {
				foreach ($times as $time_slot) {
					$slots[$result_date][$time_slot->AvailableStartTime] = $time_slot;
				}
			}
		}

		$slots = $this->sortAvailability($slots);

		return $slots;
	}

    /**
     * Runs through each date in the array sorting slots
     * by time order.
     *
     * @param $slots
     * @return mixed
     */
    private function sortAvailability($slots)
    {

        foreach ($slots as $date => $times) {
            usort($times, function ($a, $b) {
                $a = $a->AvailableStartTime;
                $b = $b->AvailableStartTime;

                if ($a == $b) {
                    return 0;
                }

                return ($a < $b) ? -1 : 1;
            });
            $slots[$date] = $times;
        }

        return $slots;
    }

    private function filterOvVenues(array $venues, string $location)
    {

        $filtered = [];

        foreach ($venues as $venue) {
            if (str_contains($venue->Name, 'OV') && str_contains($venue->ResourceCategoryName, $location)) {
                $filtered[] = $venue;
            }
        }

        return $filtered;

    }

    /**
     * Filters down results to only show available slots.
     *
     * @param $slots
     * @return array
     */
    private function filterAvailability($slots)
    {

        $filteredResults = [];

        foreach ($slots as $slot) {
            if ($slot->AvailabilityStatus === 'Available') {
                $filteredResults[$slot->AvailableDate][] = $slot;
            }
        }

        return $filteredResults;

    }

    /**
     * Adds one day onto the $start date, used when searching
     * availability.
     *
     * @param $start
     * @return string
     */
    private function getEndDateFromStart($start): string
    {
        $date = \DateTime::createFromFormat('m/d/Y', $start);
        $date->modify('last day of this month');
        return $date->format('m/d/Y');
    }

    /**
     * Finds the Zipporah ID for an appointment type.
     *
     * @param $type
     * @return array
     * @throws \Exception
     */
    public function getBookingType($type) : array
    {

        // These map to the ID's used in Zipporah.
        $appointments = array(
            'notice' => [
                'BookingTypeId' => 632632,
                'Name' => 'Notice of Marriage',
                'BookingTypeCategory' => 'Registration Process'
            ],
            'ceremony' => [
                'BookingTypeId' => 1455455,
                'Name' => 'RO Marriage Ceremony',
                'BookingTypeCategory' => 'Registration Process'
            ],
            'county' => [
                'BookingTypeId' => 1455456,
                'Name' => 'Marriage Ceremony AP',
                'BookingTypeCategory' => 'Registration Process'
            ],
            'venue' => [
                'BookingTypeId' => 1455456,
                'Name' => 'Marriage Ceremony AP',
                'BookingTypeCategory' => 'Registration Process'
            ],
        );

        if (!isset($appointments[$type])) {
            throw new \Exception('Appointment type does not exist. Must be notice, ceremony, or venue.');
        }

        return $appointments[$type];

    }

    /**
     * The name returned is a code that correlates to an
     * appointment type.
     */
    public function filterVenueByType($options, $type)
    {

        $filteredResults = [];

        foreach ($options as $option) {
            if ($this->optionIsOfType($option->Name, $type)) {
	            $option->ResourceCategoryName = str_replace('APPTS - ', '', $option->ResourceCategoryName);
	            $option->ResourceCategoryName = str_replace('CEREMONY - ', '', $option->ResourceCategoryName);
                $filteredResults[] = $option;
            }
        }

        return $filteredResults;

    }

    /**
     * Check the name of an option matches one of the
     * available types.
     *
     * CR1 -> Ceremonies for Registration Offices
     * OV1, OV2, OV3, OV4 -> Approved premises
     * RBD -> Notice of Marriage
     *
     * @param $name
     * @param $type
     * @return bool
     */
    private function optionIsOfType($name, $type)
    {

        $officeTypes = [
            'notice' => ['TAM RBD Office', 'RBD Office', 'Appointment Room 1', 'Appointment Room 2'],
            'ceremony' => ['CR 1'],
            'venue' => ['OV 1', 'OV 2', 'OV 3', 'OV 4'],
        ];

        if (in_array($name, $officeTypes[$type])) {
            return true;
        }

        return false;

    }

    /**
     * Finds the Location ID for the closest registry office
     * to a selected venue.
     *
     * @return void
     */
    public function getVenueRegistryOffice($venueId)
    {
        $terms = get_the_terms($venueId, 'area');

        if (is_wp_error($terms) || !$terms) {
            throw new \Exception('Could not find area attached to venue');
        }

        // Use the first set term
        $locationTerm = $terms[0];

		// Get the venues from Zipporah and filter down to offices calendars
        $locations = $this->listVenues('venue');
        $locations = $this->filterVenueByType($locations, 'venue');
        return $this->getLocationFromVenue($locations, $locationTerm->slug);

    }


	/**
	 * Gets all calendars for an office.
	 *
	 * @param array $offices
	 * @param string $name
	 *
	 * @return array $locations
	 */
    private function getLocationFromVenue($offices, $name)
	{

        $locations = [];

        $name = $this->getZipporahLocationName($name);

        foreach ($offices as $office) {
            if ($office->ResourceCategoryName === $name && str_contains($office->Name, 'OV')) {
                $locations[] = $office;
            }
        }

        return $locations;

    }

    /**
     * Get the location name from Zipporah that matches
     * a given location.
     *
     * @param $name
     * @return string
     */
    private function getZipporahLocationName($name)
    {

        // Sanitise name
        $name = strtolower($name);
        $name = trim($name);

        // Match wp_term to Zipporah name
        return match ($name) {
            'south-staffordshire' => 'Wombourne',
            'staffordshire-moorlands' => 'Leek',
            'east-staffordshire' => 'Burton',
            'cannock' => 'Cannock',
            'lichfield-tamworth' => 'Lichfield',
            'newcastle-under-lyme' => 'Newcastle',
            'stafford' => 'Stafford',
            default => null,
        };

    }

	/**
	 * Removes the prefix from a venue name
	 * eg 'APPTS - LEEK' -> 'Leek'
	 *
	 * @param $venue
	 * @return string
	 */
	public function removeVenuePrefix($venue): string {
		$venue = str_replace('APPTS - ', '', $venue);
		$venue = str_replace('CEREMONY - ', '', $venue);
		return ucfirst(strtolower($venue));
	}

	/**
	 * Check the API is online.
	 *
	 * @return bool
	 */
	public function aliveCheck() {
		// TODO: Implement proper alive check
		return true;
	}

    public function getBooking($id, $email)
    {
        $bookings = $this->makeRequest('/BookingSearch', [
            'BookingSearchType' => 'BookingId',
            'BookingId' => $id,
	        'EmailAddress' => $email,
        ]);
		return reset($bookings) ?? false;
    }

    public function getBookings($id, $email)
    {
        $allBookings = $this->makeRequest('/BookingSearch', [
            'BookingSearchType' => 'BookingId',
            'BookingId' => $id,
            'EmailAddress' => $email,
        ]);

        // API returns duplicate results - Need to filter out
        $bookings = [];
        foreach ($allBookings as $booking) {
            if (!isset($bookings[$booking->BookingId])) {
                $bookings[$booking->BookingId] = $booking;
            }
        }

        return $bookings;

    }

    public function listTasks($id)
    {
        return $this->makeRequest('/ListTasks', [
            'BookingId' => $id
        ]);
    }

    public function listStatuses()
    {
        return $this->makeRequest('/ListBookingStatuses');
    }

    public function listPayments()
    {
//        return $this->makeRequest('');
    }

	/**
	 * Get the name of the booking type.
	 *
	 * @param $id
	 * @return mixed
	 * @throws \Exception
	 */
	public function getBookingTypeName($id) {
		$types = collect($this->listBookingTypes());
		$type = $types->firstWhere('BookingTypeId', $id);
		return $type->Name;
	}

	/**
	 * Mark a task as complete in Zipporah.
	 *
	 * @param $taskId
	 * @return mixed
	 * @throws \Exception
	 */
	public function markTaskComplete($taskId) {
		return $this->makeRequest('/CompleteTasks', [
				['BookingTaskId' => $taskId],
		]);
	}

}