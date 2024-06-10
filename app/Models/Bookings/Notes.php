<?php

namespace Ceremonies\Models\Bookings;

use Ceremonies\Core\Model;
use Ceremonies\Models\Booking;

class Notes extends Model {

	public $timestamps = false;
	protected $table = 'booking_notes';
	protected $fillable = ['message', 'user_id', 'created_at'];
	protected $casts = [
		'created_at' => 'datetime:H:i d/m/Y',
	];

	public function booking() {
		return $this->belongsTo(Booking::class);
	}

}