<?php

namespace Ceremonies\Controllers;

use Carbon\Carbon;
use Ceremonies\Core\Bootstrap;
use Ceremonies\Models\Booking;
use Ceremonies\Models\Bookings\Choices;
use Ceremonies\Models\Bookings\ChoicesFile;
use Ceremonies\Models\Bookings\ChoicesTemplate;
use Ceremonies\Repositories\ChoicesRepository;
use Ceremonies\Services\ChoicesTemplates;
use Ceremonies\Services\FormBuilder;
use Ceremonies\Services\Token;

class ChoicesController
{
    private $choices;

    public function __construct()
    {
        $this->choices = Bootstrap::container()->get(ChoicesRepository::class);
    }

    /**
     * Find a users form relating to their booking.
     *
     * @return \WP_REST_Response
     */
    public function single()
    {
        // Get the booking
        $booking = Booking::getTokenBooking();

        // Get the form and its current results
        $choices = $this->choices->getForm($booking);

        // Just in case we've had to init a form, reload the booking
        $booking->refresh();

        $response = [
            "form_name" => $booking->choices->getFormName(),
            "in_progress" => $booking->choices->isInProgress(),
            "venue_type" => $booking->getLocationType(),
            "form_fields" => $choices,
            "complete" => $booking->choices->isSubmitted(),
        ];

        return new \WP_REST_Response(["success" => true, "data" => $response]);
    }

    /**
     * Handles autosaving of the form.
     *
     * @param \WP_REST_Request $request
     * @return mixed
     */
    public function autosave(\WP_REST_Request $request)
    {
        $booking = Booking::getTokenBooking();
        $updated = $this->choices->saveAnswers(
            $booking,
            $request->get_params()
        );
        return new \WP_REST_Response([
            "params" => $request->get_params(),
        ]);

        if ($updated) {
            $booking->choices->updated_at = Carbon::now();
            $booking->choices->save();
        }

        return new \WP_REST_Response([
            "success" => true,
            "updated" => $updated,
        ]);
    }

    /**
     * Handles the final save of the form, marking it
     * as complete.
     *
     * @param \WP_REST_Request $request
     * @return mixed
     */
    public function complete(\WP_REST_Request $request)
    {
        $booking = Booking::getTokenBooking();
        $this->choices->saveAnswers($booking, $request->get_params(), true);

        /**
         * What happens if a form is being resubmitted?
         *  - Mark form and task as resubmitted
         *  - Notify admin
         *  - Notify user
         *  - Keep denial reason to show in admin for previous reference
         *
         * How do we tell?
         *  - Check form status, if is Denied, Approved or Pending then it is a resubmission
         */

        $booking->choices->markSubmitted();
        $booking->markForReview();
        $this->choices->notifyAdminComplete($booking->choices);
        $this->choices->notifyUserComplete($booking->choices);
        $this->choices->markTaskForReview($booking);

        return new \WP_REST_Response([
            "success" => true,
            "booking" => $booking,
        ]);
    }

    public function addFiles(\WP_REST_Request $request)
    {
        $booking = Booking::getTokenBooking();
        $files = $this->choices->addFilesToForm($booking->choices, $request->get_param('question_name'));
        return new \WP_REST_Response(["success" => true, "files" => $files]);
    }

    /**
     * Deletes an array of choices form files by ID.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function deleteFiles(\WP_REST_Request $request)
    {
        $success = true;

        foreach ($request->get_param("files") as $id) {
            if (!$this->choices->removeFile($id)) {
                $success = false;
            }
        }

        return new \WP_REST_Response(["success" => $success]);
    }

    public function files(\WP_REST_Request $request)
    {
        if ($request->get_param("files") === "files") {
            return new \WP_REST_Response(["success" => true, "files" => []]);
        }
        $ids = explode(",", $request->get_param("files"));
        $files = ChoicesFile::whereIn("id", $ids)->get();
        $files = $files->map(function ($file) {
            return [
                "id" => $file->id,
                "name" => $file->file_name,
                "type" => "File",
            ];
        });
        return new \WP_REST_Response(["success" => true, "files" => $files]);
    }

    /**
     * Used to load each form in a testing environment.
     *
     * @return \WP_REST_Response
     */
    public function test(\WP_Rest_Request $request)
    {
        if (!$request->has_param("name")) {
            return null;
        }

        // Dynamically load form name from request
        $choices = ChoicesTemplates::{$request->get_param("name")}();

        $response = [
            "venue_type" => "approved_venue",
            "fields" => $choices,
            "in_progress" => false,
        ];

        return new \WP_REST_Response(["success" => true, "data" => $response]);
    }
}
