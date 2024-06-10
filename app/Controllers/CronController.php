<?php

namespace Ceremonies\Controllers;

use Ceremonies\Models\Booking;

/**
 * Any generic cron tasks used for the plugin.
 *
 * Class CronController
 * @package Ceremonies\Controllers
 */
class CronController {


	/**
	 * Clears any ceremony data that is older than 1 year.
	 *
	 * @return void
	 */
	public function dataRetention() {
		// Get all bookings that are older than 1 year and haven't been updated for a month.
		$expiredBookings = Booking::where('booking_date', '<', date('Y-m-d', strtotime('-1 year')))
		                          ->where('updated_at', '<', date('Y-m-d', strtotime('-1 month')))
		                          ->get();

		// Delete all data related to a booking and then the booking itself.
		$expiredBookings->each(function(Booking $booking) {
			$booking->choices->files()->delete();
			$booking->choices->delete();
			$booking->tasks()->delete();
//			$booking->token->delete();
			$booking->clients()->delete();
			$booking->notes()->delete();
			$booking->reminders()->delete();
			$booking->delete();
		});

	}

}





