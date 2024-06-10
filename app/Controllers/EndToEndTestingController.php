<?php

namespace Ceremonies\Controllers;

use Ceremonies\Models\Booking;

class EndToEndTestingController
{

    public function teardown(\WP_REST_Request $request)
    {
        if ($request->get_header('X-E2E-TOKEN') !== 'drGiRD1i3EbO4YI0zjEEo4TU78fN0wId') {
            throw new \Exception('Invalid token');
        }

        Booking::where('zip_reference', '83200294')->delete();

    }

}