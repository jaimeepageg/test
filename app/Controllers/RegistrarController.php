<?php

namespace Ceremonies\Controllers;

use Ceremonies\Core\Bootstrap;
use Ceremonies\Services\Zipporah\Zipporah;
use Ceremonies\Services\Zipporah\ZipporahV2;

class RegistrarController
{

    private ZipporahV2 $zipporah;

    public function __construct() {
        $this->zipporah = Bootstrap::container()->get(ZipporahV2::class);
    }

    public function test()
    {
        $response = $this->zipporah->searchAvailability(
            632632,
            3840847,
            '08/01/2024',
        );
        return new \WP_REST_Response($response);
    }

    public function venues(\WP_REST_Request $request)
    {
        // Booking type ID: 1455456
        $response = $this->zipporah->listVenues();
        $response = $this->zipporah->filterVenueByType($response, $request->get_param('type'));
        return new \WP_REST_Response($response);
    }

    public function availability(\WP_REST_Request $request)
    {
        $bookingType = $this->zipporah->getBookingType($request->get_param('type'));
        $response = $this->zipporah->searchAvailability(
            $bookingType['bookingTypeId'],
            $request->get_param('location'),
            $request->get_param('date'),
        );
        return new \WP_REST_Response($response);
    }


    /**
     * Find the locations attached for an appointment type.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     * @throws \Exception
     */
    public function locations(\WP_REST_Request $request)
    {
        $locations = $this->zipporah->listVenues($request->get_param('type'));
        $locations = $this->zipporah->filterVenueByType($locations, $request->get_param('type'));
        return new \WP_REST_Response($locations);
    }


}
