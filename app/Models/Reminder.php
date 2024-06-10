<?php

namespace Ceremonies\Models;

use Ceremonies\Core\Model;

class Reminder extends Model {

	public $timestamps = false;
	public $fillable = ['task_id', 'booking_id', 'sent', 'subject'];
	protected $casts = [
		'sent_at' => 'datetime:H:i:s d/m/Y',
	];

	public function tasks() {
		return $this->belongsToMany(Task::class);
	}

	public function booking() {
		return $this->belongsTo(Booking::class);
	}

}