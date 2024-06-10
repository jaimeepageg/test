<?php

namespace Ceremonies\Controllers;

use Ceremonies\Core\Bootstrap;
use Ceremonies\Models\Booking;
use Ceremonies\Repositories\BookingRepository;
use Ceremonies\Services\Token;
use Ceremonies\Services\Zipporah\Zipporah;
use Ceremonies\Services\Zipporah\ZipporahV2;

class AuthController
{

    private $booking;

    public function __construct()
    {
        $this->booking = Bootstrap::container()->get(BookingRepository::class);
    }

    public function auth(\WP_REST_Request $request)
    {

        try {

            // Check login data has been provided
            if (!$request->has_param('bookingRef')) {
                throw new \Exception('Booking reference is required.');
            }
            if (!$request->has_param('email')) {
                throw new \Exception('Email address is required.');
            }

            $zipporah = Bootstrap::container()->get(ZipporahV2::class);

            // Find local version of booking
            $booking = $this->booking->existsLocally(
                $request->get_param('bookingRef'),
                $request->get_param('email')
            );

            if (!$booking) {

                // Is Zipporah online?
                if (!$zipporah->aliveCheck()) {
                    throw new \Exception('Our registration service is offline, please try again later.');
                }

                // Find new booking and setup
                $booking = $this->booking->fetchNew(
                    $request->get_param('bookingRef'),
                    $request->get_param('email')
                );

            }

            // If no booking is found, user cannot login
            if (!$booking) {
                throw new \Exception('A booking could not be found with those details. These details will have been sent to you by email after your booking was made.');
            }

        } catch (\Exception $e) {
            return new \WP_REST_Response(['success' => false, 'message' => $e->getMessage()]);
        }

        // Return success and JWT
        return new \WP_REST_Response([
            'success' => true,
            'token' => Token::issue($booking->zip_reference),
            'zipporah_status' => $zipporah->aliveCheck(),
            'first_login' => $booking->zip_last_pull === null, // UX: Show user we're pulling down their data
        ]);

    }

    public function refresh()
    {
        $token = Token::get();
        if (Token::validate($token)) {
            $booking = Booking::getTokenBooking();
            return new \WP_REST_Response([
                'success' => true,
                'token' => Token::issue($booking->zip_reference),
            ]);
        } else {
            return new \WP_REST_Response([
                'success' => false,
            ]);
        }
    }


}
