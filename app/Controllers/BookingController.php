<?php

namespace Ceremonies\Controllers;

use Carbon\Carbon;
use Ceremonies\Core\Bootstrap;
use Ceremonies\Models\Booking;
use Ceremonies\Models\Bookings\ChoicesQuestion;
use Ceremonies\Models\Payment;
use Ceremonies\Models\Task;
use Ceremonies\Repositories\BookingRepository;
use Ceremonies\Repositories\ChoicesRepository;
use Ceremonies\Repositories\FormsRepository;
use Ceremonies\Services\Mail;
use Ceremonies\Services\PostcodeLookup;
use Ceremonies\Services\Token;
use Ceremonies\Services\Zipporah\ZipporahV2;

class BookingController {

	private $booking;
	private $choices;
	private $forms;

	public function __construct() {
		$this->booking = Bootstrap::container()->get(BookingRepository::class);
		$this->choices = Bootstrap::container()->get(ChoicesRepository::class);
		$this->forms = Bootstrap::container()->get(FormsRepository::class);
	}

    /**
     * Lists all bookings with filtering and pagination.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
	public function index(\WP_REST_Request $request) {

		// Filter results if query string is present
		if ($request->get_params()) {
			$bookings = $this->booking->getFilteredResults($request->get_params());
		} else {
			$bookings = Booking::paginate(15)
                ->whereNotIn('status', [Booking::STATUS_IN_PROGRESS, Booking::STATUS_COMPLETED, Booking::STATUS_CANCELLED]);
//                ->orderBy('id', 'ASC');
		}

//        $pagination = [
//            'current_page' => $bookings->currentPage(),
//            'last_page' => $bookings->lastPage(),
//            'per_page' => $bookings->perPage(),
//            'total' => $bookings->total(),
//            'items' => collect($bookings->items())->map(function($booking) {
//                return [
//				'id' => $booking->id,
//				'zipporah_reference' => $booking->zip_reference,
//				'primary_email' => $booking->email_address,
//				'registration_office' => $booking->office,
//				'ceremony_date' => Carbon::parse($booking->ceremony_date)->format('d/m/Y'),
//				'status' => 'Status',
//			];
//            })
//        ];

		return new \WP_REST_Response(['success' => true, 'bookings' => $bookings]);
	}

	/**
	 * View a single booking.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function single(\WP_REST_Request $request) {
		$booking = Booking::where('id', $request->get_param('id'))->first();
		$booking->zip_notes = $booking->zip_notes ? nl2br($booking->zip_notes) : '';
        $booking->balance = $booking->getBalance();
		return new \WP_REST_Response(['success' => true, 'booking' => $booking]);
	}

	/**
	 * Gets all the data needed for the dashboard view.
	 *
	 * @return \WP_REST_Response
	 */
	public function dashboard() {

		// Get booking ref from JWT
		$bookingRef = Token::getTokenName();
		$booking = Booking::where('zip_reference', $bookingRef)->with('tasks')->first();

		// Fetch fresh data from Zipporah and update local data
		$this->booking->refreshData($booking);

		// Pull out data for dashboard
		$data = $this->booking->getDashboardData($booking);

		return new \WP_REST_Response(['success' => true, 'data' => $data, 'expiring' => Token::isExpiring()]);

	}

	/**
	 * Creates a booking with initial data from
	 * Zipporah.
	 *
	 * @return \WP_REST_Response
	 */
	public function init() {

		$bookingRef = Token::getTokenName();
		$booking = Booking::where('zip_reference', $bookingRef)->first();

        if (!$booking->isInitialised()) {
		    $this->booking->initialBookingSetup($booking);
        }

        // Delay purely for UX on the frontend, users need chance to read the message that appears
		sleep(3);

		return new \WP_REST_Response(['success' => true, 'booking' => $booking]);

	}

	/**
	 * Get the choices for a single booking.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function singleChoices(\WP_REST_Request $request) {

		$choices = Booking::where('id', $request->get_param('id'))->first()->choices;

        // If the user has not started the forms, $choices will be null
        if (!$choices) {
            return new \WP_REST_Response(['success' => false, 'message' => 'No choices found for this booking']);
        }

        $choices->load(['questions', 'files']);
		$questions = $this->choices->formatQuestions($choices);
		return new \WP_REST_Response(['success' => true, 'data' => ['booking' => $choices, 'questions' => $questions]]);

	}

	/**
	 * Get the clients for a single booking.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function singleClients(\WP_REST_Request $request) {
		$booking = Booking::where('id', $request->get_param('id'))->first();
		return new \WP_REST_Response(['success' => true, 'data' => $booking->clients]);
	}

	/**
	 * Get the notes for a single booking.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function singleNotes(\WP_REST_Request $request) {
		$booking = Booking::where('id', $request->get_param('id'))->first();
		return new \WP_REST_Response(['success' => true, 'data' => $booking->notes]);
	}

	/**
	 * Get the tasks for a single booking.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function singleTasks(\WP_REST_Request $request) {
		$booking = Booking::where('id', $request->get_param('id'))->first();
		$booking->tasks->map(function($task) {
			$task->status = ucfirst($task->status);
			return $task;
		});
		return new \WP_REST_Response(['success' => true, 'data' => $booking->tasks]);
	}

	/**
	 * Update a booking task.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
    public function updateTask(\WP_REST_Request $request)
    {

        $task = Task::where('id', $request->get_param('taskId'))->first();
        $task->update([
            'status' => $request->get_param('status'),
            'note' => $request->get_param('note')
        ]);

		if ($task->status === 'Complete') {
			$zipporah = Bootstrap::container()->get(ZipporahV2::class);
			$zipporah->markTaskComplete($task->id);
		}

        return new \WP_REST_Response(['success' => true]);
    }

	/**
	 * Get the payments task info for the booking.
	 *
	 * @return \WP_REST_Response
	 */
	public function payments() {
		$booking = Booking::getTokenBooking();
		$paymentTask = $booking->tasks->where('name', Task::$payBalanceName)->first();
		return new \WP_REST_Response([
            'success' => true,
            'data' => [
                'task' => $paymentTask,
                'balance' => $booking->getBalance(),
                'payments' => $booking->payments,
            ]
        ]);
	}

	/**
	 * Get the notice task info for the booking.
	 *
	 * @return \WP_REST_Response
	 */
	public function notice() {
		$booking = Booking::getTokenBooking();
		$noticeTask = $booking->tasks->where('name', Task::$bookNoticeName)->first();
		return new \WP_REST_Response([
			'success' => true,
			'data' => [
				'task' => $noticeTask,
                'message' => $noticeTask->getStatusMessage(),
				'url' => $booking->getBookingNoticeUrl(),
				'correctRegion' => PostcodeLookup::isAllowed($booking->getPostcode())
			]
		]);
	}

	/**
	 * Get the choices task and choices for the current booking.
	 *
	 * @return \WP_REST_Response
	 */
	public function choices() {
		$booking = Booking::getTokenBooking()->load(['tasks', 'choices']);
		$choicesTask = $booking->tasks->where('name', Task::$submitChoicesName)->first();
		return new \WP_REST_Response([
			'success' => true,
			'data' => [
				'task' => $choicesTask,
				'choices' => $booking->choices,
			]
		]);
	}

	/**
	 * Handle a contact form submission from within the
	 * portal.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function form(\WP_REST_Request $request) {

		$booking = Booking::getTokenBooking();
		$sendToEmail = $this->forms->getRegistrationOfficeEmail($booking->office);

		// Pull user info from booking
		$data = [
			'ceremony_id' => $booking->id,
			'zipporah_reference' => $booking->zip_reference,
			'primary_email' => $booking->email_address,
			'registration_office' => $booking->office,
			'ceremony_type' => $booking->type,
			'ceremony_date' => $booking->booking_date,
		];
		$data = array_merge($data, $request->get_params());

		try {
			$formSubmission = $this->forms->storeSubmission($data, $sendToEmail);
			$booking->addNote('User sent in form submission with ID ' . $formSubmission->id);
			$this->forms->sendMail($formSubmission);
		} catch (\Exception $e) {
			return new \WP_REST_Response([
				'success' => 'false',
				'message' => $e->getMessage()
			], 500);
		}

		return new \WP_REST_Response(['success' => true]);

	}

	/**
	 * Deny the users choices.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function denyChoices(\WP_REST_Request $request) {

		$booking = Booking::where('id', $request->get_param('id'))->with('tasks')->first();
		$choicesTask = $booking->tasks->where('name', Task::$submitChoicesName)->first();
		$choicesTask->update([
			'status' => 'Rejected',
			'note' => $request->get_param('reason'),
		]);

		// Update form status to rejected
		$booking->choices->markDenied();

		// Notify user of rejection
		$this->choices->sendRejectionEmail($booking, $choicesTask);

		return new \WP_REST_Response(['success' => true]);

	}

	/**
	 * Approve the users ceremony choices.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function approveChoices(\WP_REST_Request $request) {

		$booking = Booking::where('id', $request->get_param('id'))->with('tasks')->first();
		$choicesTask = $booking->tasks->where('name', Task::$submitChoicesName)->first();
		$choicesTask->update([
			'status' => 'Approved',
			'completed_at' => Carbon::now(),
		]);

		// Update form status to approved
		$booking->choices->markApproved();
        $booking->markApproved();

		// Notify user of approval
		$this->choices->sendApprovalEmail($booking);

		// Mark as complete in Zipporah
		if ($choicesTask->zip_id) {
			$zipporah = Bootstrap::container()->get(ZipporahV2::class);
			$zipporah->markTaskComplete($choicesTask->zip_id);
        }

		return new \WP_REST_Response(['success' => true]);

	}

	/**
	 * View a payment for a booking.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function singlePayment(\WP_REST_Request $request) {

		$booking = Booking::getTokenBooking();
		$payment = Payment::where('id', $request->get_param('id'))
		                  ->where('bookingId', $booking->id)
		                  ->first();

		return new \WP_REST_Response(['success' => true, 'data' => $payment]);

	}

	/**
	 * Update a single choice's answer.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function updateChoice(\WP_REST_Request $request) {
		$choice = ChoicesQuestion::where('id', $request->get_param('id'))->first();
		$choice->answer = $request->get_param('answer');
		$choice->save();

		return new \WP_REST_Response(['success' => true, 'data' => $choice]);
	}

	/**
	 * Generate a PDF output of a booking.
	 *
	 * @param \WP_REST_Request $request
	 * @return void
	 */
	public function bookingPdf(\WP_REST_Request $request) {

		$booking = Booking::where('id', $request->get_param('id'))->with(['clients', 'tasks', 'notes', 'choices'])->first();
		$booking->choices->questions = $this->choices->formatQuestions($booking->choices);

//        print('<pre>'.print_r($booking->getContactName(), true).'</pre>');
//        print('<pre>'.print_r($booking->choices->questions, true).'</pre>');
//        exit();
        
		$pdf = $this->booking->generatePdf($booking);
		$fileName = 'booking-' . $booking->zip_reference . '-'. $booking->getBookingDateForFile() .'.pdf';

		// Output the PDF as a response
		header('Content-Type: application/pdf');
		header('Content-Disposition: attachment; filename="' . $fileName . '"');
		$pdf->export($fileName);
		exit();

//		return new \WP_REST_Response(['success' => true, 'data' => $booking->getBookingNoticeUrl()]);
	}

	/**
	 * Email the registration office with the
	 * booking details.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function adminEmail(\WP_REST_Request $request) {

		$booking = Booking::where('id', $request->get_param('id'))->with(['clients', 'tasks', 'notes', 'choices'])->first();
		$booking->choices->questions = $this->choices->formatQuestions($booking->choices);

		$mailable = Mail::create('Ceremony Details')
		                ->sendToOffice($booking->office)
		                ->with(['booking' => $booking])
		                ->send();

		return new \WP_REST_Response(['success' => true, 'data' => ['sent' => $mailable->sent]]);

	}

    /**
     * Unlock a form for editing.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function unlockForm($request)
    {
        $booking = Booking::where('id', $request->get_param('id'))->first();

        if (!$booking) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Booking not found'], 404);
        }

        $booking->choices->unlock();
        sleep(2); // For UX improvement
        return new \WP_REST_Response(['success' => true]);

    }

    public function singleUpdate(\WP_REST_Request $request)
    {
        $booking = Booking::where('id', $request->get_param('id'))->first();
        $booking->update($request->get_params());
        return new \WP_REST_Response(['success' => true, 'booking' => $booking]);
    }

}
