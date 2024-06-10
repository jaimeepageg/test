<?php

namespace Ceremonies\Models;

use Ceremonies\Core\Model;

class Token extends Model {

	public $timestamps = false;
	protected $fillable = ['booking_id', 'token', 'expiry'];
	protected $casts = [
		'expiry' => 'datetime:Y-m-d',
	];

	public function booking() {
		return $this->belongsTo(Booking::class);
	}

}