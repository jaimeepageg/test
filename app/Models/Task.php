<?php

namespace Ceremonies\Models;

use Carbon\Carbon;
use Ceremonies\Core\Model;
use Mpdf\Tag\P;

class Task extends Model {

	public $timestamps = false;

	protected $fillable = ['zip_id', 'status', 'note', 'is_complete'];

	protected $casts = [
		'complete_by' => 'datetime:d/m/Y',
	];

	/**
	 * Task names
	 */
	public static $submitChoicesName = 'Submit choices form';
	public static $payBalanceName = 'Pay Balance';
	public static $bookNoticeName = 'Book appointment to give notice';

	public $statuses = [
		'Not Started',
		'In Progress',
		'Review',
		'Rejected',
		'Complete',
	];

	public function booking() {
		return $this->belongsTo(Booking::class);
	}

	public function reminders() {
		return $this->hasMany(Reminder::class);
	}

	public function markAsReview() {
		$this->updated_at = Carbon::now();
		$this->status = 'Review';
		$this->save();
	}

	public function markAsComplete() {
		$this->completed_at = Carbon::now();
		$this->updated_at = Carbon::now();
		$this->status = 'Complete';
		$this->save();
	}

	/**
	 * Any messages with {time_to_complete} will be replaced with
	 * the due date in human time
	 *
	 * @var array[]
	 */
	private static array $statusMessages = [
		'Submit choices form' => [
			'pending' => 'Your ceremony choices are required to be submitted by {time_to_complete}',
			'review' => 'Your choices have been submitted for review, you will be notified when they are approved.',
			'rejected' => 'There were some issues with your choices, please review and resubmit.',
			'approved' => 'Your choices have been approved.',
			'complete' => 'Your choices have been approved.',
		],
		'Book appointment to give notice' => [
			'pending' => 'You need to book an appointment to give notice. This needs to be completed by {time_to_complete}',
			'complete' => 'Your notice appointment has been booked.',
		],
		'Pay Balance' => [
			'pending' => 'You need to pay the outstanding balance. This needs to be completed by {time_to_complete}',
			'complete' => 'You have paid your balance.',
		],
	];

	/**
	 * Get the due date in human time difference.
	 *
	 * @return string
	 */
	public function dueDateInHumanTime() {
//        return Carbon::parse($this->complete_by)->longRelativeToNowDiffForHumans(Carbon::now());
        return Carbon::parse($this->complete_by)->format('d/m/Y');
	}

	/**
	 * Get the status message for the task.
	 *
	 * @return string
	 */
	public function getStatusMessage() {
        if ($this->name === self::$bookNoticeName && $this->status === 'In Progress') {
            return $this->note;
        } else if ($this->status) {
			$message = self::$statusMessages[$this->name][strtolower($this->status)];
			return str_replace('{time_to_complete}', $this->dueDateInHumanTime(), $message);
		}
		return '';
	}

}