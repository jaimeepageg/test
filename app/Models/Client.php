<?php

namespace Ceremonies\Models;

use Ceremonies\Core\Model;

class Client extends Model {

    public $timestamps = false;
    protected $fillable = ['first_name', 'last_name', 'email', 'phone', 'is_primary'];

	public function booking() {
		return $this->belongsTo(Booking::class);
	}

	public function getName(  ) {
		return $this->first_name . ' ' . $this->last_name;
	}

}