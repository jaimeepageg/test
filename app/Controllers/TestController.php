<?php

namespace Ceremonies\Controllers;

use Carbon\Carbon;
use Ceremonies\Core\Bootstrap;
use Ceremonies\Models\Booking;
use Ceremonies\Models\Bookings\Choices;
use Ceremonies\Models\Client;
use Ceremonies\Models\Payment;
use Ceremonies\Models\Task;
use Ceremonies\Repositories\BookingRepository;
use Ceremonies\Repositories\ChoicesRepository;
use Ceremonies\Services\Helpers;
use Ceremonies\Services\Mail;
use Ceremonies\Services\Zipporah\Zipporah;
use Ceremonies\Services\Zipporah\ZipporahV2;

class TestController
{

    // TODO: Request 500's
    public function search(\WP_REST_Request $request)
    {
        $zip = Bootstrap::container()->get(ZipporahV2::class);
        $booking = $zip->getBooking($request->get_param('id'), $request->get_param('email'));
        return new \WP_REST_Response($booking);
    }

    // TODO: Request 500's
    public function tasks(\WP_REST_Request $request)
    {
        $zip = Bootstrap::container()->get(ZipporahV2::class);
        $tasks = $zip->listTasks($request->get_param('id'));
        return new \WP_REST_Response($tasks);
    }

    // OK
    public function statuses(\WP_REST_Request $request)
    {
        $zip = Bootstrap::container()->get(ZipporahV2::class);
        $statuses = $zip->listStatuses();
        return new \WP_REST_Response($statuses);
    }

    // TODO: Not sure what this is for
    public function payment(\WP_REST_Request $request)
    {
        $zip = Bootstrap::container()->get(ZipporahV2::class);
        $statuses = $zip->addPayment();
        return new \WP_REST_Response($statuses);
    }

    // OK
    public function categories()
    {
        $zip = Bootstrap::container()->get(ZipporahV2::class);
        $types = $zip->listBookingTypes();

//		foreach($types as $type) {
//			(new BookingType())->populate($type);
//		}

        return new \WP_REST_Response($types);
    }

    public function venues()
    {
        $zip = Bootstrap::container()->get(ZipporahV2::class);
        $venues = $zip->listVenues('ceremony');
        return new \WP_REST_Response($venues);
    }

    public function getBooking()
    {

        $booking = Booking::where('id', 32)->first();
        $booking->email_address = 'c.underhill@wethrive.agency';

        $repo = new ChoicesRepository();
        $mailable = $repo->sendApprovalEmail($booking);

        return new \WP_REST_Response([
            $mailable,
        ]);
    }

    public function mailTest()
    {

        header('Content-Type: text/html');

        $office = 'email@example.com';
        $choices = Choices::all()->first();
//        $task = Task::where('id', 5)->first();
        $booking = Booking::all()->first();

        $mailable = Mail::create("Ceremony Choices Require Attention")
            ->sendTo('')
            ->with(['reason' => 'an reason', 'reg_office_email' => $booking->getRegOfficeEmail()])
            ->display();

        return new \WP_REST_Response($mailable);
    }

    public function cleanup()
    {

        $zip = Bootstrap::container()->get(ZipporahV2::class);

        // Tasks exist in the DB, but have no ID
        Task::whereNull('zip_id')->get()->map(function ($task) use ($zip) {

            if ($task->booking->zip_reference) {

                $nameOptions = [];
                if ($task->name === Task::$bookNoticeName) {
                    $nameOptions = ['Partner 1 Notice recieved', 'Brides NOM paperwork received', 'Grooms NOM paperwork received'];
                } else if ($task->name === Task::$payBalanceName) {
                    $nameOptions = ['Choices Received'];
                } else if ($task->name === Task::$submitChoicesName) {
                    $nameOptions = ['Payment Received'];
                }

                $zipTask = collect($zip->listTasks($task->booking->zip_reference))
                    ->first(function ($item) use ($nameOptions) {
                        return in_array($item->name, $nameOptions);
                    });

                if ($zipTask && $task->zip_id === null) {
                    $task->complete_by = Carbon::parse($zipTask->completeBy);
                    $task->status = $zipTask->isComplete ? 'complete' : 'pending';
                    $task->zip_id = $zipTask->id;
                    $task->save();
                }
            }
        });

        // Clients exist and have an ID but no data
        Client::whereNull('first_name')
            ->whereNull('last_name')
            ->get()
            ->map(function ($client) use ($zip) {

                // Get client from Zip
                $data = $zip->getClient($client->zip_id);
                if ($data) {
                    $client->fill([
                        'first_name' => $data->firstName,
                        'last_name' => $data->surname,
                        'email' => Helpers::hideEmail($data->email),
                        'phone' => Helpers::hidePhone($data->telephone),
                    ]);
                    $client->save();
                }

            });

    }

}
