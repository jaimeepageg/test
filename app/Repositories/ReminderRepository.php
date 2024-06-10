<?php

namespace Ceremonies\Repositories;

use Carbon\Carbon;
use Ceremonies\Models\Reminder;
use Ceremonies\Models\Task;
use Ceremonies\Services\Mail;

class ReminderRepository {

	public function sendNotice(Task $task) {

		$mailable = Mail::create('Book Notice Reminder')
		                ->sendTo($task->booking->email_address)
		                ->with(['task' => $task])
		                ->send();

		// Update task
		$this->saveReminder($task, $mailable->sent);

	}

	public function sendChoices(Task $task) {

		$mailable = Mail::create('Ceremony Choices Reminder')
		                ->sendTo($task->booking->email_address)
		                ->with(['task' => $task])
		                ->send();

		// Update task
		$this->saveReminder($task, $mailable->sent);

	}

	public function sendFees(Task $task) {

		$mailable = Mail::create('Outstanding Balance Reminder')
		                ->sendTo($task->booking->email_address)
		                ->with(['task' => $task])
		                ->send();

		// Update task
		$this->saveReminder($task, $mailable->sent);

	}

	private function saveReminder(Task $task, bool $sent) {

		Reminder::create([
			'task_id'    => $task->id,
			'booking_id' => $task->booking->id,
			'sent'       => $sent ? Carbon::now() : null,
			'subject'    => $task->name,
		]);

	}

}