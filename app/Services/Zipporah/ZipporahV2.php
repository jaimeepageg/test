<?php

namespace Ceremonies\Services\Zipporah;

use Ceremonies\Services\Logger;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;

class ZipporahV2
{
    /**
     * The sandpit/sandbox API URL.
     * @var string
     */
    private string $sandpit_uri = "RegAPI.Sandpit.New/";

    /**
     * The production API URL.
     * @var string
     */
    private string $live_uri = "RegAPI.Live/";

    /**
     * The base URI for the API.
     * @var string
     */
    private string $base_uri = "https://staffordshire.zipporah.co.uk/";

    /**
     * The current API JWT.
     * @var string
     */
    private string $token = "";

    /**
     * The API refresh token.
     *
     * @var string
     */
    private string $refreshToken = "";

    /**
     * Use the class in development mode.
     *
     * @var bool
     */
    private bool $productionApi = false;


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
            "base_uri" => $this->base_uri . $this->getUri(),
            "handler" => $this->stack,
            "defaults" => [
                "headers" => [
                    "Content-Type" => "application/json",
                    "Accept" => "application/json",
                ],
            ],
        ]);
    }

    /**
     * Gets the current token, either from the current
     * object or a transient.
     *
     * @return false|string
     */
    private function getToken()
    {
        if ($this->token) {
            return $this->token;
        }

        $transient = get_transient("sc-zip-token");
        if ($transient) {
            $this->token = $transient;
            return $this->token;
        }

        return false;
    }

    /**
     * Get the current refresh token.
     *
     * @return false|string
     */
    private function getRefreshToken()
    {
        if ($this->refreshToken) {
            return $this->refreshToken;
        }

        $transient = get_transient("sc-zip-refresh");
        if ($transient) {
            $this->refreshToken = $transient;
            return $this->refreshToken;
        }

        return false;
    }

    /**
     * Set token as a transient and on the current
     * object. Uses a refresh token instead of
     * expiring.
     *
     * @param $token
     * @return void
     */
    private function setToken($token)
    {
        set_transient("sc-zip-token", $token);
        $this->token = $token;
    }

    /**
     * Sets the refresh token as a transient.
     *
     * @param $refreshToken
     * @return void
     */
    private function setRefreshToken($refreshToken)
    {
        set_transient("sc-zip-refresh", $refreshToken);
        $this->refreshToken = $refreshToken;
    }

    /**
     * Gets an access token from Zipporah
     *
     * @return void
     * @throws \Exception
     */
    private function fetchToken()
    {
        // If we have a refresh token, refresh current credentials
        if ($this->getRefreshToken()) {
            $this->refreshToken();
            return;
        }

        $tap = $this->setupTapMiddlware();

        // If no refresh token, get fresh credentials with standard auth
        $response = $this->client->post("Authentication/login", [
            "handler" => $tap($this->stack),
            "json" => $this->getAuthDetails(),
        ]);

        Logger::log("Fetching new token", $response);
        // Throw an exception if we have the wrong creds
        if ($response->getStatusCode() !== 200) {
            throw new \Exception(
                "Invalid API username/password - Token not generated"
            );
        }

        // Get data and set tokens
        $data = json_decode($response->getBody(), true);
        $this->setToken($data["token"]);
        $this->setRefreshToken($data["refreshToken"]);
    }

    /**
     * Use the current token/refresh token and fetch
     * a fresh one from the API.
     *
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function refreshToken()
    {
        //        print('<pre>'.print_r([
        //            $this->getToken(),
        //            $this->getRefreshToken()
        //        ], true).'</pre>');

        try {
            // Refresh token
            $response = $this->client->post("Authentication/refresh", [
                "json" => [
                    "accessToken" => $this->getToken(),
                    "refreshToken" => $this->getRefreshToken(),
                ],
            ]);

            // Throw an exception if we have the wrong creds
            if ($response->getStatusCode() !== 200) {
                throw new \Exception(
                    "Invalid API username/password - Token not generated"
                );
            }

            // Get data and set tokens
            $data = json_decode($response->getBody(), true);
            $this->setToken($data["token"]);
            $this->setRefreshToken($data["refreshToken"]);
        } catch (\Exception $e) {
            $this->clearCurrentTokens();
            $this->fetchToken();
        }
    }

    /**
     * Checks if the current token has expired.
     *
     * @return bool
     */
    private function hasTokenExpired()
    {
        if (!$this->token) {
            $this->getToken();
        }

        $tokenData = $this->decodeJWT();

        if (!$tokenData) {
            return true;
        }

        return strtotime("now") > $tokenData->exp;
    }

    /**
     * Decode the current JWT and return the payload
     * as an object.
     *
     * @return mixed
     */
    private function decodeJWT()
    {
        try {
            $token = $this->getToken();
            if (!$token) {
                return null;
            }
        list($header, $payload, $signature) = explode(".", $token);
            return json_decode(base64_decode($payload));
        } catch (\Exception $e) {
            return null;
        }
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
        return "sc-" . md5($url . json_encode($params));
    }

    /**
     * Gets the appropriate URI depending on
     * $useDevMode.
     *
     * @return string
     */
    private function getUri(): string
    {
        return $this->productionApi ? $this->live_uri : $this->sandpit_uri;
    }

    /**
     * Gets the appropriate auth headers depending on
     * $useDevMode.
     *
     * @return string[]
     */
    private function getAuthDetails(): array
    {
        return [
            "userName" => "WeThrive",
            "password" => "Staffordshire1",
        ];
    }

    /**
     * Gets our Zipporah User ID.
     *
     * @return int
     */
    private function getUserId()
    {
        return 8340;
    }

    /**
     * Handles making requests directly to Zipporah's API.
     *
     * @param string $url
     * @param string $method
     * @param array $data
     * @return mixed
     * @throws \Exception
     */
    private function makeRequest($url, $method = "GET", $data = [])
    {

        // Refresh token if it's expired
        if ($this->hasTokenExpired() || !$this->getToken()) {
            $this->fetchToken();
        }

        // Setup middleware to log requests
        $tap = $this->setupTapMiddlware();

        $requestConfig = [
            "handler" => $tap($this->stack),
        ];

        if ($this->urlRequiresAuth($url)) {
            $requestConfig['headers'] = [
                "Authorization" => "Bearer " . $this->getToken(),
            ];
        }

        // Attach data as JSON if not a GET request
        if (!empty($data) && $method !== "GET") {
            $requestConfig["json"] = $data;
        } elseif (!empty($data) && $method === "GET") {
            $requestConfig["query"] = $data;
        }


        try {
            // Make request to Zipporah
            $response = $this->client->request($method, $url, $requestConfig);

            // Decode response and cache.
            $responseData = json_decode($response->getBody());

            // Only cache if a GET request
             if ($method === "GET") {
                 $this->cacheResponse($url, $data, $responseData);
             }

            // Convert to json and return
            return $responseData;
        } catch (\Exception $e) {
            Logger::log("Zipporah error: " . print_r($e->getMessage(), true));
//            echo "<p>There has been an error: " . $e->getMessage() . "</p>";
            return [];
        }
    }

    /**
     * Watches any requests sent and logs the data for them.
     *
     * @return callable|\Closure
     */
    private function setupTapMiddlware(): callable|\Closure
    {
        // Create a middleware that echoes parts of the request.
        return Middleware::tap(
            function ($request) {
                Logger::log("Zipporah request to: " . $request->getUri());
            },
            function ($response) {
//                Logger::log("Zipporah response: " .
//                        print_r($response->, true)
//                );
            }
        );
    }

    /**
     * Checks if a URL requires auth headers
     * to be sent with request.
     *
     * @param string $url
     * @return bool
     */
    private function urlRequiresAuth(string $url)
    {
        /**
         * Track the URL's that do not require auth as
         * there are less of them. Return the inverse
         * of this result.
         */
        $urlsWithoutAuth = [
            'Booking/GetAvailableResourceCategoriesForBookingTypeAsync',
            'Booking/GetAvailability'
        ];

        return !in_array($url, $urlsWithoutAuth);
    }

    /**
     * Gets the booking types from Zipporah.
     *
     * @return mixed
     * @throws \Exception
     */
    public function listBookingTypes(): mixed
    {
        return $this->makeRequest("Booking/BookingTypes");
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
        Logger::log('Finding venues for ' . $type);
        $venues = $this->makeRequest(
            "Booking/GetAvailableResourceCategoriesForBookingTypeAsync",
            "GET",
            [
                "bookingTypeId" => $data["bookingTypeId"],
            ]
        );
        return $this->filterVenueByType($venues, $type);
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
        $slots = $this->makeRequest("Booking/GetAvailability", "POST", [
            "resourceFk" => $resourceId,
            "bookingTypeFk" => $bookingTypeId,
            "startDate" => $this->formatDate($start),
            "endDate" => $this->getEndDateFromStart($start),
        ]);

        return $this->filterAvailability($slots);
    }

    public function getCountyBuildingsAvailability($date)
    {
        $venues = $this->listVenues("county");
        $venues = $this->filterOvVenues($venues, "Stafford");

        $slots = [];
        foreach ($venues as $venue) {
            $results = $this->searchAvailability(
                1455456,
                $venue->id,
                $date
            );
            foreach ($results as $result_date => $times) {
                foreach ($times as $time_slot) {
                    $slots[$result_date][
                        $time_slot->AvailableStartTime
                    ] = $time_slot;
                }
            }
        }

        $slots = $this->sortAvailability($slots);

        return $slots;
    }

    public function getVenueAvailability($bookingType, $venues, $date)
    {
        $slots = [];
        foreach ($venues as $venue) {
            $results = $this->searchAvailability(
                1455456,
                $venue->resources[0]->id,
                $date
            );
//            dd($results);
            foreach ($results as $result_date => $times) {
                foreach ($times as $time_slot) {
                    $slots[$result_date][
                        $time_slot->startDate
                    ] = $time_slot;
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
                $a = $a->startDate;
                $b = $b->startDate;

                if ($a == $b) {
                    return 0;
                }

                return $a < $b ? -1 : 1;
            });
            $slots[$date] = $times;
        }

        return $slots;
    }

    private function filterOvVenues(array $venues, string $location)
    {
        $filtered = [];

        foreach ($venues as $venue) {

            $matchingCalendars = array_filter($venue->resources, function($calendar) {
               return str_contains($calendar->name, 'OV');
            });

            if (str_contains($venue->name, $location) && $matchingCalendars > 0) {
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
            if (!$slot->booked) {
                $filteredResults[$slot->date][] = $slot;
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
        $date = \DateTime::createFromFormat("m/d/Y", $start);
        $date->modify("last day of this month");
        return $date->format(\DateTimeInterface::ATOM);
    }

    /**
     * Finds the Zipporah ID for an appointment type.
     *
     * @param $type
     * @return array
     * @throws \Exception
     */
    public function getBookingType($type): array
    {
        // These map to the ID's used in Zipporah.
        $appointments = [
            "notice" => [
                "bookingTypeId" => 632632,
                "Name" => "Notice of Marriage",
                "BookingTypeCategory" => "Registration Process",
            ],
            "ceremony" => [
                "bookingTypeId" => 1455455,
                "Name" => "RO Marriage Ceremony",
                "BookingTypeCategory" => "Registration Process",
            ],
            "county" => [
                "bookingTypeId" => 1455456,
                "Name" => "Marriage Ceremony AP",
                "BookingTypeCategory" => "Registration Process",
            ],
            "venue" => [
                "bookingTypeId" => 1455456,
                "Name" => "Marriage Ceremony AP",
                "BookingTypeCategory" => "Registration Process",
            ],
        ];

        if (!isset($appointments[$type])) {
            throw new \Exception(
                "Appointment type does not exist. Must be notice, ceremony, or venue."
            );
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
        $idsInUse = [];

        foreach ($options as $option) {
            foreach ($option->resources as $resource) {
                if ($this->optionIsOfType($resource->name, $type) && !in_array($option->id, $idsInUse)) {
                    $option->name = str_replace("APPTS - ", "", $option->name);
                    $option->name = str_replace(
                        "CEREMONY - ",
                        "",
                        $option->name
                    );
                    $filteredResults[] = $option;
                    $idsInUse[] = $option->id;
                }
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
            "notice" => [
                "TAM RBD Office",
                "RBD Office",
                "Appointment Room 1",
                "Appointment Room 2",
            ],
            "ceremony" => ["CR 1"],
            "venue" => ["OV 1", "OV 2", "OV 3", "OV 4"],
        ];

        if ($type === "county" || in_array($name, $officeTypes[$type])) {
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
        $terms = get_the_terms($venueId, "area");

        if (is_wp_error($terms) || !$terms) {
            throw new \Exception("Could not find area attached to venue");
        }

        // Use the first set term
        $locationTerm = $terms[0];

        // Get the venues from Zipporah and filter down to offices calendars
        $locations = $this->listVenues("venue");

        $locations = $this->filterVenueByType($locations, "venue");
//        dd($this->getLocationFromVenue($locations, $locationTerm->slug));
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

//        dd($offices, $name);

        foreach ($offices as $office) {
//            print('<pre>'.print_r([$office->name, $name, $office->resources[0]->name], true).'</pre>');
            if (
                 $office->name === $name &&
                str_contains($office->resources[0]->name, "OV")
            ) {
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
            "south-staffordshire" => "Wombourne",
            "staffordshire-moorlands" => "Leek",
            "east-staffordshire" => "Burton",
            "cannock" => "Cannock",
            "lichfield-tamworth" => "Lichfield",
            "newcastle-under-lyme" => "Newcastle",
            "stafford" => "Stafford",
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
    public function removeVenuePrefix($venue): string
    {
        $venue = str_replace("APPTS - ", "", $venue);
        $venue = str_replace("CEREMONY - ", "", $venue);
        return ucfirst(strtolower($venue));
    }

    /**
     * Check the API is online.
     *
     * @return bool
     */
    public function aliveCheck()
    {
        // TODO: Implement proper alive check
        return true;
    }

    /**
     * FIXME: All response give 500 errors.
     *
     * @param $id
     * @param $email
     * @return false|mixed
     * @throws \Exception
     */
    public function getBooking($id, $email)
    {
        $bookings = $this->makeRequest(
            "api/BookingSearch/BookingSearch",
            "GET",
            [
                "bookingSearchType" => "BookingId",
                "bookingId" => $id,
            ]
        );
        return reset($bookings) ?? false;
    }

    public function getBookings($id, $email)
    {
        // FIXME: 404'ing
        $allBookings = $this->makeRequest("/BookingSearch", "POST", [
            "bookingId" => $id,
            "bookingSearchType" => "BookingId",
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

    public function getRelatedBookings($multipleBookingId)
    {
        return $this->makeRequest(
            "api/BookingSearch/MultipleBookingSearchDetails",
            "GET",
            [
                "multipleBookingId" => $multipleBookingId,
            ]
        );
    }

    // FIXME: All response give 500 errors.
    public function listTasks($id)
    {
        return $this->makeRequest("Booking/GetAllTask", "GET", [
            "encryptedBookingId" => $id,
        ]);
    }

    public function listStatuses()
    {
        return $this->makeRequest("Booking/GetAllBookingStatuses");
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
    public function getBookingTypeName($id)
    {
        $types = collect($this->listBookingTypes());
        $type = $types->firstWhere("BookingTypeId", $id);
        return $type->Name;
    }

    /**
     * Mark a task as complete in Zipporah.
     *
     * @param $taskId
     * @return mixed
     * @throws \Exception
     */
    public function markTaskComplete($taskId)
    {
        // This endpoint returns null with a 200 response. As long as no error is thrown,
        // assume it's worked.
        return $this->makeRequest(
            "Booking/CompleteTasks?userId=" . $this->getUserId(),
            "POST",
            [$taskId]
        );
    }

    /**
     * Format date in the ATOM format for Zipporah.
     *
     * @param string $date
     * @return string
     */
    private function formatDate(string $date)
    {
        $date = \DateTime::createFromFormat("m/d/Y", $date);
        return $date->format(\DateTimeInterface::ATOM);
    }

    /**
     * Gets the date of a ceremony booking.
     *
     * @param string $bookingId
     * @return string
     * @throws \Exception
     */
    public function getCeremonyDate(string $bookingId)
    {
        return $this->makeRequest("Booking/GetCeremonyDate", "GET", [
            "encryptedBookingId" => $bookingId,
        ]);
    }

    /**
     * Get the details of a client attached to a booking.
     *
     * @return void
     */
    public function getClient($id)
    {
        return $this->makeRequest("Booking/PersonDetails", "GET", [
            "encryptedClientId" => $id,
        ]);
    }

    public function completePayment($id, $paymentRef)
    {
        return $this->makeRequest("Booking/MarkPaymentItemsSuccess", "POST", [
            "paymentUpdateModels" => [
                [
                    "paymentId" => $id,
                    "thirdPartyPaymentReference" => $paymentRef,
                    "paymentStatus" => "Success",
                    "paymentMethod" => "CardHolderNotPresent",
                ],
            ],
        ]);
    }

    private function clearCurrentTokens()
    {
        delete_transient("sc-zip-token");
        delete_transient("sc-zip-refresh");
        $this->token = "";
        $this->refreshToken = "";
    }
}
