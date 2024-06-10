<?php

namespace Ceremonies\Models\Bookings;

use Ceremonies\Core\Model;

class ChoicesSection extends Model {

	public function choices() {
		return $this->belongsTo(Choices::class);
	}

	public function questions() {
		return $this->hasMany(ChoicesQuestion::class, 'form_id', 'id');
	}

}