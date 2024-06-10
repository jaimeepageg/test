<?php

namespace Ceremonies\Repositories;

use Ceremonies\Core\Bootstrap;
use Ceremonies\Models\Booking;
use Ceremonies\Models\Package;
use Ceremonies\Models\Payment;
use Ceremonies\Models\Task;
use Ceremonies\Services\Capita\Capita;
use Ceremonies\Services\Mail;
use Ceremonies\Services\Zipporah\Zipporah;
use Ceremonies\Services\Zipporah\ZipporahV2;

class PaymentsRepository
{

    /**
     * Create a new user for a payment invocation.
     *
     * @param $data
     * @return int|\WP_Error
     */
    public function createUser($data)
    {

        $user = wp_insert_user(array(
            'user_login' => $this->formatLoginName($data['name']),
            'user_pass' => $data['pass'],
            'user_email' => $data['contact_email'],
            'display_name' => $data['name'],
            'user_status' => 1,
            'show_admin_bar_front' => false,
            'role' => $data['account_type'],
        ));

        // If insert user returns WP_Error - throw exception
        if (!is_int($user)) {
            throw new \Exception('Error setting up new user: ' . $user->get_error_message());
        }

        // Add extra data to user
        $user_key = 'user_' . $user;

        // Loop over each ACF key and update user data.
        $keys = array('public_contact_number', 'address', 'contact_number', 'contact_email', 'contact_name');

        foreach ($keys as $key) {
            update_field($key, $data[$key], $user_key);
        }

        return $user;

    }

    /**
     * Formats a normal name to be used for a username.
     * Eg: This Name -> this-name
     *
     * @param $name
     * @return string
     */
    private function formatLoginName($name)
    {
        $name = str_replace(' ', '-', $name);
        return strtolower($name);
    }

    /**
     * Creates a new payment.
     *
     * @param array $requestData
     * @param array $capitaData
     * @param mixed $entity - This could be a User ID or a Booking object.
     * @return Payment $payment
     */
    public function createPayment($requestData, $capitaData, $entity)
    {
        $payment = new Payment($capitaData);
        $payment->state = $capitaData['transactionState'];
        $payment->amountPaid = $requestData['payment_line_amount'];

		if (is_a($entity, 'Ceremonies\Models\Booking')) {
			$payment->packageName = 'Ceremony Fee';
			$payment->bookingId = $entity->id;
		} else {
			$payment->packageName = $requestData['payment_line_name'];
            $payment->userId = $entity;
		}

		$payment->save();
        return $payment;
    }

    /**
     * Gets a payment from the DB by id and user_id.
     *
     * @param $payment_id
     * @param $user_id
     * @return mixed
     */
    public function getPayment($id, $userId)
    {
        return Payment::where([
            ['id', $id],
            ['userId', $userId],
        ])->first();
    }

	private function getBookingPayment($id, $bookingId) {
		return Payment::where([
			['id', $id],
			['bookingId', $bookingId],
		])->first();
	}

    /**
     * Updates the payment with all the extra data from
     * a completed payment response from Capita.
     *
     * @param Payment $payment
     * @param array $data Capita Response
     *
     * @return Payment $payment
     */
    public function completePayment(Payment $payment, array $data): Payment {

		if (!isset($data['paymentResult']['paymentDetails'])) {
			throw new \Exception('Server Error - Payment provider failed to capture payment.');
		}

        // Extract payment details from request
        $details = $data['paymentResult']['paymentDetails'];

        // Add extra data to payment
        $payment->fill([
            'transactionId' => $details['paymentHeader']['uniqueTranId'],
            'state' => $data['transactionState'],
        ]);
        $payment->cardNumber = $this->formatCardNumber($details['authDetails']['maskedCardNumber']);
        $payment->cardType = sprintf('%s %s', $details['authDetails']['cardDescription'], $details['authDetails']['cardType']);
        $payment->save();

        return $payment;

    }

    /**
     * Set a payment to be marked as failed.
     *
     * @param $payment
     * @return mixed
     */
    public function paymentFailed($payment)
    {
        $payment->state = 'FAILED';
        $payment->save();
        return $payment;
    }

    /**
     * Makes sure only the final four digits are stored in the database.
     *
     * @param $number
     * @return string
     */
    private function formatCardNumber($number)
    {
        $lastFour = substr($number, -4, 4);
        return sprintf("XXXX-XXXX-XXXX-%s", $lastFour);
    }

    /**
     * Checks if the payment cookie isset, if not then the payment
     * process has not been started.
     *
     * @return true
     * @throws \Exception
     */
    public function paymentStarted()
    {
        if (!isset($_COOKIE['sc-pt']) && str_contains('|', $_COOKIE['sc-pt'])) {
            throw new \Exception('Payment process has not been started');
        }
        return true;
    }

	public function ceremonyPaymentStarted()
	{
		if (!isset($_COOKIE['sc-cap-pt']) && str_contains('|', $_COOKIE['sc-cap-pt'])) {
			throw new \Exception('Payment process has not been started');
		}
		return true;
	}

    /**
     * Checks if the renewal payment cookie isset, if not then the
     * payment process has not been started.
     *
     * @return true
     * @throws \Exception
     */
    public function renewalStarted()
    {
        if (!isset($_COOKIE['sc-r-pt']) && str_contains('|', $_COOKIE['sc-r-pt'])) {
            throw new \Exception('Renewal payment process has not been started');
        }
        return true;
    }

    /**
     * Sets up a new package after a completed payment.
     *
     * @param $payment
     * @return Package
     */
    public function setupPackage($payment)
    {

        $package = new Package([
            'name' => $payment->packageName,
            'user_id' => $payment->userId,
            'total' => $payment->amountPaid,
        ]);

        // Set the package type from user role (Venue or Supplier)
        $user = get_user_by('id', $payment->userId);
        $package->type = $this->getTypeFromUserRole($user);

        // Set package start date
        $package->startDate = date('Y-m-d H:i:s');

        // Add package expiry date/time
        if (str_contains('2 Years', $package->name)) {
            $package->expiryDate = date('Y-m-d H:i:s', strtotime('+2 years'));
        } else {
            $package->expiryDate = date('Y-m-d H:i:s', strtotime('+1 year'));
        }

        // Save the package
        $package->save();

        // Associate the two and save
        $payment->package()->associate($package);
        $payment->save();

        // Refresh the package
        $package->refresh();

        return $package;

    }

    /**
     * Updates the renewal date and payment reference.
     *
     * @param $payment
     * @param $packageId
     * @return Package
     */
    public function updatePackage($payment, $packageId)
    {
        $package = Package::where('id', $packageId)->first();

        $currentDate = $package->expiryDate ?? null;
        if ($currentDate === null || $currentDate->isPast()) {
            $currentDate = date('c');
        }

        // Add package expiry date/time
        if (str_contains('2 Years', $package->name)) {
            $package->expiryDate = date('Y-m-d H:i:s', strtotime($currentDate . ' +2 years'));
        } else {
            $package->expiryDate = date('Y-m-d H:i:s', strtotime($currentDate . ' +1 year'));
        }

        // Reset renewal email reminder sent
        $package->lastReminderSent = null;

        // Save the package
        $package->save();

        // Associate the two and save
        $payment->package()->associate($package);
        $payment->save();

        // Refresh the package
        $package->refresh();

        return $package;
    }

    /**
     * Gets the role from a WP_User and removes the
     * prefix.
     *
     * @param \WP_User $user
     * @return string
     */
    private function getTypeFromUserRole($user): string
    {
        $role = $user->roles[0];
        return str_replace('ad_', '', $role);
    }

    /**
     * Gets the user of an advertisement package.
     *
     * @param $packageId
     * @return int
     */
    public function getUser($packageId): int
    {
        $package = Package::where('id', $packageId)->first();
        $user = get_user_by('id', $package->user_id);
        return $user->ID;
    }

    /**
     * Processes a returning payment request from Capita.
     *
     * @return Package
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function processPayment()
    {
        // Check if the payment process has been started
        $this->paymentStarted();

        // Grab the payment and user ID from the cookie
        [$id, $userId] = explode("|", $_COOKIE['sc-pt']);

        // Pull the payment from the database
        $payment = $this->getPayment($id, $userId);

        // Pull Capita from the container and query the payment
        $capita = Bootstrap::container()->get(Capita::class);
        $response = $capita->queryPayment($payment->scpReference);

        // Update the payment
        $payment = $this->completePayment($payment, $response);

        $package = $this->setupPackage($payment);
        $this->sendNewPackageEmails($package);

        return $package;
    }

    /**
     * Sends out notification emails that a new package
     * has been created.
     *
     * @param Package $package
     * @return void
     */
    private function sendNewPackageEmails(Package $package)
    {

        $package->loadUser();

        // Send notice to admin
        Mail::create('Advertising Package Set Up')
            ->with($package->toArray())
            ->sendTo($package->user->user_email)
            ->send();

        // Send notice to admin
        Mail::create('New Package')
            ->with($package->toArray())
            ->sendTo('c.underhill@wethrive.agency')
            ->send();

    }

    /**
     * Processes a returning reneprocessRenewalwal payment request from
     * Capita.
     *
     * @return Package
     */
    public function processRenewal()
    {
        // Check if the payment process has been started
        $this->renewalStarted();

        // Grab the payment and user ID from the cookie
        [$id, $userId, $packageId] = explode("|", $_COOKIE['sc-r-pt']);

        // Pull the payment from the database
        $payment = $this->getPayment($id, $userId);

        // Pull Capita from the container and query the payment
        $capita = Bootstrap::container()->get(Capita::class);
        $response = $capita->queryPayment($payment->scpReference);

        // Update the payment
        $payment = $this->completePayment($payment, $response);
        return $this->updatePackage($payment, $packageId);
    }

	/**
	 * Processes a returning payment request from Capita.
	 *
	 * @return Booking
	 * @throws \DI\DependencyException
	 * @throws \DI\NotFoundException
	 */
	public function processCeremonyPayment()
	{
		// Check if the payment process has been started
		$this->ceremonyPaymentStarted();

		// Grab the payment and user ID from the cookie
		[$id, $bookingId] = explode("|", $_COOKIE['sc-cap-pt']);

		// Pull the payment from the database
		$payment = $this->getBookingPayment($id, $bookingId);

		// Pull Capita from the container and query the payment
		$capita = Bootstrap::container()->get(Capita::class);
		$response = $capita->queryPayment($payment->scpReference);

		// Update the payment
		$this->completePayment($payment, $response);

		// Update payment task
		$booking = Booking::where('id', $bookingId)->first();

        $zipporah = Bootstrap::container()->get(ZipporahV2::class);

		// Update payment lines in Zipporah and mark as paid
        $booking->payments->where('status', '!=', 'Success')->each(function($paymentItem) use ($zipporah, $payment) {
            [$response] = $zipporah->completePayment(
                $paymentItem->zip_id,
                $payment->transactionId,
            );
            if ($response->success) {
                $paymentItem->markAsPaid();
            }
        });

        $paymentTask = $booking->tasks->where('name', Task::$payBalanceName)->first();
        $paymentTask->markAsComplete();

        // Update Payment Task in Zipporah
        if ($paymentTask->zip_id) {
            $zipporah->markTaskComplete($paymentTask->zip_id);
        }

	}

}