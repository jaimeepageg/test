<?php

namespace Ceremonies\Models\Bookings;

use Ceremonies\Core\Model;

class ChoicesTemplate extends Model {

	public $timestamps = false;

	public function getFields() {
		return json_decode($this->fields, true);
	}

}