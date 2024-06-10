<?php

namespace Ceremonies\Controllers;

use Carbon\Carbon;
use Ceremonies\Core\Bootstrap;
use Ceremonies\Models\Task;
use Ceremonies\Repositories\ChoicesRepository;
use Ceremonies\Repositories\ReminderRepository;

/**
 * All reminder methods should have limits in place to avoid
 * mass emailing users. Limit should be to run 20 times per
 * call.
 *
 * Class ReminderController
 * @package Ceremonies\Controllers
 */
class ReminderController {

    private ChoicesRepository $choices;
    private ReminderRepository $reminder;

	public function __construct() {
		$this->choices  = Bootstrap::container()->get(ChoicesRepository::class);
		$this->reminder = Bootstrap::container()->get(ReminderRepository::class);
	}

	/**
	 * Send out book giving notice email
	 * reminders.
	 *
	 * @return \WP_REST_Response
	 */
	public function notice() {

		// Needs to run based on ceremony date not task completion date
		// When it is 12 weeks before ceremony and task has not been completed
		// Send reminder once a week for 4 weeks

		// Get booking notice tasks that are 12 weeks before ceremony date
		$tasks = Task::with('reminders')
		             ->where('name', Task::$bookNoticeName)
                     ->where('status', 'pending')
		             ->whereHas('booking', function ($query) {
			             $query->whereBetween('booking_date', [
                             Carbon::now()->addWeeks(8),
                             Carbon::now()->addWeeks(12)
                         ]);
		             })->get();

		// Loop over each task
		$tasks->each(function ($task) {

			// Check if any reminders have been sent in the last 7 days
			$recentlySent = false;
			$task->reminders->each(function ($reminder) use (&$recentlySent) {
				if ($reminder->sent && $reminder->sent >= Carbon::now()->subDays(7)) {
					$recentlySent = true;
				}
			});

			// If none have been sent, send one
			if (!$recentlySent) {
				$this->reminder->sendNotice($task);
			}

		});

		return new \WP_REST_Response($tasks);

	}

	/**
	 * Send out submit choices email
	 * reminders.
	 *
	 * @return \WP_REST_Response
	 */
	public function choices() {

		// Needs to run based on ceremony date not task completion date
		// When it is 8 weeks before ceremony and task has not been completed
		// Send reminder once a week until ceremony.
		$tasks = Task::with('reminders')
		             ->where('name', Task::$submitChoicesName)
                     ->whereIn('status', ['pending', 'Rejected'])
		             ->whereHas('booking', function ($query) {
			             $query->whereBetween('booking_date', [
                             Carbon::now(),
                             Carbon::now()->addWeeks(8)
                         ]);
		             })->get();

		// Loop over each task
		$tasks->each(function ($task) {

			// Check if any reminders have been sent in the last 7 days
			$recentlySent = false;
			$task->reminders->each(function ($reminder) use (&$recentlySent) {
				if ($reminder->sent && $reminder->sent >= Carbon::now()->subDays(7)) {
					$recentlySent = true;
				}
			});

			// If none have been sent, send one
			if (!$recentlySent) {
				$this->reminder->sendChoices($task);
			}

		});

		return new \WP_REST_Response($tasks);

	}

	/**
	 * Send out pay balance email
	 * reminders.
	 *
	 * @return \WP_REST_Response
	 */
	public function fees() {

		$tasks = Task::with('reminders')
		             ->where('name', Task::$payBalanceName)
                     ->where('status', 'pending')
		             ->whereHas('booking', function ($query) {
			             $query->whereBetween('booking_date', [
                             Carbon::now(),
                             Carbon::now()->addWeeks(8)
                         ]);
		             })->get();

		// Emails are to be sent once a week for 4 weeks

		// Loop over each task
		$tasks->each(function ($task) {

			// Check if any reminders have been sent in the last 7 days
			$recentlySent = false;
			$task->reminders->each(function ($reminder) use (&$recentlySent) {
				if ($reminder->sent && $reminder->sent >= Carbon::now()->subDays(7)) {
					$recentlySent = true;
				}
			});

			// If none have been sent, send one
			if (!$recentlySent) {
				$this->reminder->sendFees($task);
			}

		});


		return new \WP_REST_Response($tasks);

	}

}