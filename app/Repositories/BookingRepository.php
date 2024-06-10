<?php

namespace Ceremonies\Repositories;

use Carbon\Carbon;
use Ceremonies\Core\Bootstrap;
use Ceremonies\Models\Booking;
use Ceremonies\Models\BookingPayment;
use Ceremonies\Models\Client;
use Ceremonies\Models\Task;
use Ceremonies\Services\Helpers;
use Ceremonies\Services\PdfGenerator;
use Ceremonies\Services\Zipporah\Zipporah;
use Ceremonies\Services\Zipporah\ZipporahV2;

class BookingRepository
{

    private $zipporah;

    public function __construct()
    {
        $this->zipporah = Bootstrap::container()->get(ZipporahV2::class);
    }

    /**
     * Checks if a booking has been pulled down and stored
     * from Zipporah.
     *
     * @param $bookingRef
     * @param $email
     * @return Booking|false
     */
    public function existsLocally($bookingRef, $email)
    {
        return Booking::where([
            ['zip_reference', $bookingRef],
            ['email_address', $email]
        ])->first() ?? false;
    }

    /**
     * Fetches a booking from Zipporah and pulls down
     * data to store locally.
     *
     * @param $bookingRef
     * @param $email
     * @return Booking|false
     */
    public function fetchNew($bookingRef, $email)
    {

        $zipBooking = $this->zipporah->getBooking($bookingRef, $email);

        if ($zipBooking->bookingTypeName === 'Notice of Marriage') {
            throw new \Exception('The booking information used relates to your Notice of Marriage Appointment. Please use your ceremony booking reference to access your ceremony account. If you cannot locate this information, please contact your local registration office.');
        } else if (in_array($zipBooking->bookingTypeName, Booking::$invalidTypes)) {
            throw new \Exception('Your booking type cannot be used for this account. Please contact your local registration office for any issues or enquiries.');
        }

        // Booking does not exist.
        if (!$zipBooking) {
            return false;
        }

        // Double check result found is an exact match
        if ($zipBooking->email != $email || $zipBooking->bookingId != $bookingRef) {
            return false;
        }

        if (Booking::alreadyExists($zipBooking)) {
            return false;
        }

        // Create booking locally
        $booking = new Booking();
        $booking->zip_reference = $bookingRef;
        $booking->email_address = $email;
        $booking->office = $this->zipporah->removeVenuePrefix($zipBooking->resourceCategoryName);
        $booking->type = $zipBooking->bookingTypeName;
        $ceremonyDate = Carbon::parse($this->zipporah->getCeremonyDate($zipBooking->bookingId));
        $booking->booking_date = $ceremonyDate->toDateTimeString();
        $booking->zip_notes = $zipBooking->note;
        $booking->location = $zipBooking->venueName ?? '';
        $booking->phone_number = $zipBooking->telephone ?? '';
        $booking->raw_data = json_encode($zipBooking);
        $booking->save();

        // Removed fields after change to ZipporahV2
        //		$booking->type = $this->zipporah->getBookingTypeName($zipBooking->BookingTypeId);
        //		$booking->zip_related_bookings = $zipBooking->MultipleBookingId; // Missing
        //		$booking->booking_cost = $zipBooking->TotalCost; // Missing

        // Return
        return $booking;
    }

    /**
     * Synchronises the data between the portal and
     * Zipporah.
     *
     * @param Booking $booking
     * @return void
     */
    public function refreshData(Booking $booking)
    {

        // Only run if cache is stale
        if (!$booking->isCacheStale()) {
            return;
        }

        // Only run if booking has been initialised
        //		if (!$booking->hasInitialised()) {
        //			$this->initialBookingSetup($booking);
        //			return;
        //		}

        // Pull fresh data and update the main booking
        //        $zipBooking = $this->zipporah->getBooking($booking->zip_reference, $booking->email_address);
        //        $booking->populate($zipBooking);

        // Update clients
        //        $this->updateBookingClients($booking, $zipBooking->Clients);

        // Check if a notice booking has been added and update
        $noticeTask = $booking->tasks->where('name', Task::$bookNoticeName)->first();
        if (!$noticeTask->completed_at) {
            $noticeBooking = $this->getNoticeBooking($booking);
            if ($noticeBooking) {
                $this->updateNoticeTask($booking, $noticeBooking);
            }
            $booking->updateCacheTime();
        }
    }

    /**
     * Pulls together all the data needed for the dashboard.
     *
     * @param Booking $booking
     *
     * @return array[]
     */
    public function getDashboardData(Booking $booking)
    {

        $noticeTask = $booking->tasks->where('name', Task::$bookNoticeName)->first();
        $paymentTask = $booking->tasks->where('name', Task::$payBalanceName)->first();
        $choicesTask = $booking->tasks->where('name', Task::$submitChoicesName)->first();

        return [
            'id' => $booking->id,
            'booking_date' => $booking->getBookingDate(),
            'ceremony_has_passed' => $booking->hasCeremonyPast(),
            'data_last_updated' => $booking->zip_last_pull,
            'office' => $booking->office,
            'office_email' => $booking->getRegOfficeEmail(),
            'ceremony_type' => $booking->type,
            'location' => $booking->location,
            'clients' => $booking->clients,
            'notice_status' => $noticeTask->status ?? 'pending',
            'notice_message' => $noticeTask->getStatusMessage(),
            'choices_status' => $choicesTask->status,
            'choices_message' => $choicesTask->getStatusMessage(),
            'payment_status' => $paymentTask->status,
            'payment_message' => $paymentTask->getStatusMessage(),
            'zipporah_status' => $this->zipporah->aliveCheck(),
        ];
    }

    /**
     * Pulls down data from Zipporah and sets up the
     * initial booking within the portal.
     *
     * @param Booking $booking
     * @return void
     */
    public function initialBookingSetup(Booking $booking)
    {

        /**
         * To figure out:
         * - What venue? - Cannot currently be found
         * - What payments have been taken so far? - Waiting on Zipporah
         */

        // Pull data for booking from Zipporah
        $data = $this->zipporah->getBooking(
            $booking->zip_reference,
            $booking->email_address
        );

        $booking->populate($data);

        // Setup clients
        foreach ($data->encryptedClientIds as $id) {

            $user = $this->zipporah->getClient($id);

            $client = new Client();
            $client->first_name = $user->firstName;
            $client->last_name = $user->surname;
            $client->email = Helpers::hideEmail($user->email);
            $client->phone = Helpers::hidePhone($user->telephone);
            $client->is_primary = $booking->email_address === $user->email;
            $client->zip_id = $id;
            $client->booking_id = $booking->id;

            $client->save();
        }

        // Setup OUR tasks - do not mistake for Zipporah tasks

        // Get task data from Zipporah
        $tasks = $this->getBookingTasksList();
        $zipTasks = collect($this->zipporah->listTasks($booking->zip_reference));

        foreach ($tasks as $taskItem) {

            $zipTask = [];
            if (is_array($taskItem['zip_name'])) {
                $zipTask = $zipTasks->whereIn('name', $taskItem['zip_name'])->first();
            } else {
                $zipTask = $zipTasks->where('name', $taskItem['zip_name'] ?? $taskItem['name'])->first();
            }


            $task = new Task();
            $task->name = $taskItem['name'];
            $task->status = 'pending';

            if ($zipTask) {
                $task->complete_by = Carbon::parse($zipTask->completeBy);
                $task->status = $zipTask->isComplete ? 'complete' : 'pending';
                $task->zip_id = $zipTask->id;
            }

            $booking->tasks()->save($task);
        }

        // Parse payment data
        if (count($data->paymentItems) > 0) {
            $this->createBookingPayments($booking, $data->paymentItems);
        }


        // TODO: Waiting on Zipporah to fix staging env to be able to set bookings
        // Then we can re-implement this.
        // If there is a notice booking, update the task
        $noticeBooking = $this->getNoticeBooking($booking);
        if ($noticeBooking) {
            $this->updateNoticeTask($booking, $noticeBooking);
        }
    }

    /**
     * Filter bookings by an array of filters.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getFilteredResults(array $filters)
    {

        $fieldsMap = [
            'zipporah_reference' => 'zip_reference',
            'email' => 'email_address',
            'status' => 'status',
        ];

        $query = Booking::query();

        if (!isset($filters['status'])) {
            $query->whereNotIn('status', [Booking::STATUS_IN_PROGRESS, Booking::STATUS_COMPLETED, Booking::STATUS_CANCELLED]);
        } else if ($filters['status'] === 'all') {
            unset($filters['status']); // We do not want this going into the query
        }

        foreach ($filters as $key => $value) {
            if (isset($fieldsMap[$key])) {
                // Use the mapped column name instead of the request key
                $query->where($fieldsMap[$key], $value);
            }
        }

        // Offices need to be handled separately due to having to
        // group multiple offices together.
        if (isset($filters['registration_office'])) {
            switch ($filters['registration_office']) {
                case 'all':
                    break;
                case 'Newcastle and Leek':
                    $query->whereIn('office', ['Newcastle', 'Leek']);
                    break;
                case 'Cannock and Wombourne':
                    $query->whereIn('office', ['Cannock', 'Wombourne']);
                    break;
                default:
                    $query->where('office', $filters['registration_office']);
            }
        }

        // Check ceremony date range
        if (isset($filters['ceremony_date'])) {
            [$from, $to] = explode(',', $filters['ceremony_date']);
            $query->whereBetween('booking_date', [Carbon::parse($from), Carbon::parse($to)]);
        }

        // Paginate results
        return $query->paginate(perPage: 15, page: $filters['page'] ?? 1);
    }

    /**
     * Generate a PDF for a booking.
     *
     * @param Booking $booking
     * @return PdfGenerator
     */
    public function generatePdf(Booking $booking)
    {
        return PdfGenerator::create()
            ->setHeader('Booking Ref: ' . $booking->zip_reference)
            ->setFooter('Booking Ref: ' . $booking->zip_reference)
            ->loadAndFill('booking-export', $booking)
            ->prepare();
    }

    /**
     * Find a giving notice booking for a booking.
     *
     * @param Booking $booking
     * @return array|false
     */
    public function getNoticeBooking(Booking $booking)
    {
        $bookings = $this->zipporah->getRelatedBookings($booking->zip_related_bookings);

        if (!$bookings) {
            return false;
        }

        $bookings = array_filter($bookings, function ($item) use ($booking) {
            return $item->bookingId !== $booking->zip_reference && $item->bookingTypeName === 'Notice of Marriage';
        });
        return reset($bookings) ?? false;
    }

    public function updateNoticeTask(Booking $booking, $noticeBooking)
    {
        $bookedStatuses = ['Confirmed', 'Complete', 'Underway', 'Arrived', 'Provisional', 'Temporary'];
        if ($noticeBooking && in_array($noticeBooking->bookingStatus, $bookedStatuses)) {
            $task = $booking->tasks->where('name', Task::$bookNoticeName)->first();

            $task->note = 'Your giving notice ceremony is booked for ' . Carbon::parse($noticeBooking->startTime)->format('d/m/Y H:i');
            $task->status = 'Pending';

            // Update the status of the booking
            if ($noticeBooking->bookingStatus === 'Confirmed') {
                $task->status = 'In Progress';
            } elseif ($noticeBooking->bookingStatus === 'Complete') {
                $task->status = 'Complete';
                $task->completed_at = Carbon::now();
            }

            $task->save();
        }
    }

    /**
     * Update the clients for a booking.
     *
     * @param $booking
     * @param $clients
     * @return void
     */
    public function updateBookingClients($booking, $clients)
    {
        foreach ($clients as $client) {
            $clientModel = $booking->clients->where('zip_id', $client->Id)->first();
            $clientModel->update([
                'first_name' => $client->FirstName,
                'last_name' => $client->LastName,
                'email' => Helpers::hideEmail($client->EmailAddress),
                'phone' => Helpers::hidePhone($client->TelephoneNumber),
                'is_primary' => $client->IsPrimary,
            ]);
        }
    }

    /**
     * Returns the list of tasks for a new booking with
     * local and Zipporah names.
     *
     * @return array[]
     */
    public function getBookingTasksList(): array
    {
        return [
            [
                'name' => 'Book appointment to give notice',
                'zip_name' => [
                    'Partner 1 Notice recieved',
                    'Brides NOM paperwork received',
                    'Grooms NOM paperwork received',
                    '1st Notice received',
                ]
            ],
            [
                'name' => 'Submit choices form',
                'zip_name' => ['Choices Received'],
            ],
            [
                'name' => 'Pay Balance',
                'zip_name' => ['Payment Received'],
            ],
        ];
    }

    /**
     * Add any payments related to an incoming booking.
     *
     * @param Booking $booking
     * @param array $payments
     * @return void
     */
    private function createBookingPayments(Booking $booking, array $payments): void
    {
        foreach ($payments as $data) {
            $payment = new BookingPayment([
                'zip_id' => $data->paymentItemId,
                'item_name' => $data->narrative,
                'amount' => $data->itemCost,
                'status' => $data->paymentStatus,
            ]);
            $booking->payments()->save($payment);
        }
    }
}
