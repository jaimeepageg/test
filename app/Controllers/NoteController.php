<?php

namespace Ceremonies\Controllers;

use Carbon\Carbon;
use Ceremonies\Models\Booking;

class NoteController {

	public function index(\WP_REST_Request $request) {
		$booking = Booking::where('id', $request->get_param('id'))
	        ->with('notes')
	        ->first();

		$notes = $booking->notes->map(function($note) {
			if ($note->user_id) {
				$note->user = get_user_by('id', $note->user_id);
			}
			return $note;
		});

		return new \WP_REST_Response(['success' => true, 'notes' => $notes]);
	}

	public function insert(\WP_REST_Request $request) {

		$booking = Booking::where('id', $request->get_param('id'))->first();

		$booking->notes()->create([
			'message' => $request->get_param('note'),
			'created_at' => Carbon::now(),
		    'user_id' => $request->get_param('user')
		]);

		return new \WP_REST_Response(['success' => true]);
	}

}