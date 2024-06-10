<?php

namespace Ceremonies\Controllers;

use Ceremonies\Core\Bootstrap;
use Ceremonies\Models\Booking;
use Ceremonies\Models\BookingPayment;
use Ceremonies\Services\Capita\Capita;
use Ceremonies\Repositories\PaymentsRepository;
use Ceremonies\Models\Payment;

class PaymentsController
{

    private $repository;

    /**
     * Set up the repository
     */
    public function __construct()
    {
        $this->repository = Bootstrap::container()->get(PaymentsRepository::class);
    }

    /**
     * An example intent response, used for testing only.
     *
     * @return array
     */
    private function intentResponse()
    {
        return [
            "requestId" => "529",
            "scpReference" => "mnmamqgh9hnypsowyfudgafbkq3ew99",
            "transactionState" => "IN_PROGRESS",
            "invokeResult" => [
                    "status" => "SUCCESS",
                "redirectUrl" => "https://sbsctest.e-paycapita.com:443/scp/scpcli?ssk=switut0nsnevoehmrsfvonkdqhe7wfv"
            ]
        ];
    }

	/**
	 * List all payments for the admin panel.
	 *
	 * @return \WP_Rest_Response
	 */
    public function index() {
        $payments = Payment::orderBy('id', 'DESC')->get();
        $payments = $payments->map(function($payment){
            return [
                'id' => $payment->id,
                'user_id' => $payment->userId,
                'package' => $payment->packageName,
                'amount' => $payment->amountPaid,
                'state' => $payment->state,
                'updated_at' => $payment->getUpdatedAt(),
            ];
        });
        return new \WP_Rest_Response($payments);
    }

	/**
	 * View an individual payment for the admin panel.
	 *
	 * @param \WP_Rest_Request $request
	 * @return \WP_Rest_Response
	 */
    public function single(\WP_Rest_Request $request) {
        $payment = Payment::where('id', $request->get_param('id'))->first();
        $payment->getUser();
        return new \WP_Rest_Response($payment);
    }

    /**
     * Invokes a payment request with Capita.
     *
     * @return \WP_REST_Response
     */
    public function invoke()
    {

        // Sanitize the incoming data and create a new user
        $request = sanitize_array($_POST);
        $user = $this->repository->createUser($request);

        // Pull Capita from the container and invoke the payment
        $capita = Bootstrap::container()->get(Capita::class);
        $response = $capita->invokePayment($request);

        // Save the payment data in the database and return URL to the client.
        $payment = $this->repository->createPayment($request, $response, $user)->toArray();
        return new \WP_REST_Response(array_merge($response, $payment));

    }

	public function bookingInvoke(\WP_REST_Request $request) {

		$booking = Booking::getTokenBooking();

		// Add each
        $paymentData = [];
        foreach ($booking->payments->where('status', 'PaymentRequired') as $item) {
            $paymentData[] = [
                'id' => $item->zip_id,
                'description' => $item->item_name,
                'amount' => $item->amount
            ];
        }

        $data = [
            'payment_line_name' => 'Staffordshire Ceremonies Booking Payment',
            'payment_line_amount' => $booking->payments->where('status', 'PaymentRequired')->sum('amount'),
            'items' => $paymentData,
        ];

		// Pull Capita from the container and invoke the payment
		$capita = Bootstrap::container()->get(Capita::class);
		$response = $capita->invokePayment($data);

		// Save the payment data in the database and return URL to the client.
		$payment = $this->repository->createPayment($data, $response, $booking)->toArray();
		return new \WP_REST_Response(array_merge($response, $payment));

	}

    /**
     * Invokes a payment request with Capita.
     *
     * @return \WP_REST_Response
     */
    public function renewal(\WP_REST_Request $request)
    {

        // Sanitize the incoming data and create a new user
        $user = $this->repository->getUser($request->get_param('package_id'));

        // Pull Capita from the container and invoke the payment
        $capita = Bootstrap::container()->get(Capita::class);
        $response = $capita->invokePayment($request->get_body_params());

        // Save the payment data in the database and return URL to the client.
        $payment = $this->repository->createPayment($request, $response, $user)->toArray();
        return new \WP_REST_Response(array_merge($response, $payment, ['packageId' => $request->get_param('package_id')]));

    }

    /**
     * Processes the return response from Capita to the site. Checking
     * if the payment has been completed.
     *
     * Probably violates RESTful by using cookies - sorry.
     */
    public function return()
    {

        // If 'r' is in cookie, returning payment is for a renewal
        try {
            if (isset($_COOKIE['sc-cap-pt'])) {
	            $this->repository->processCeremonyPayment();
	            $url = '/ceremony-account-portal/#/payments/complete';
            } else if (isset($_COOKIE['sc-r-pt'])) {
                $package = $this->repository->processPayment();
	            $url = sprintf('/payment-complete?id=%s', $package->id);
            } else {
	            $package = $this->repository->processRenewal();
	            $url = sprintf('/payment-complete?id=%s', $package->id);
            }
        } catch (\Exception $e) {
            $url = '/payment-failed?message=' . $e->getMessage();
        }


        // Redirect to complete page
        wp_redirect($url);

        // Exit instead of return -> Forces the redirect to work.
        exit();

    }


    /**
     * Handles an erroneous return to the site from Capita.
     *
     * @return void
     * @throws \Exception
     */
    public function error()
    {

        // Check if the payment process has been started
        $this->repository->paymentStarted();

        // Grab the payment and user ID from the cookie
        [$id, $user_id] = explode("|", $_COOKIE['sc-pt']);

        // Pull the payment from the database
        $payment = $this->repository->getPayment($id, $user_id);

        // Pull Capita from the container and query the payment
        $capita = Bootstrap::container()->get(Capita::class);
        $response = $capita->queryPayment($payment->scpReference);

        // Update the payment
        $this->repository->paymentFailed($payment);

        wp_redirect('/payment-failed');

        // Exit instead of return -> Forces the redirect to work.
        exit();

    }

}