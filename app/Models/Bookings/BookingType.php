<?php

namespace Ceremonies\Models\Bookings;

use Ceremonies\Core\Model;

class BookingType extends Model {

	protected $guarded = [];
	public $timestamps = false;

	public function populate($data) {
		$this->fill([
			'type_id' => $data->BookingTypeId,
			'name' => $data->Name,
			'category' => $data->BookingTypeCategory,
		]);
		$this->save();
	}

}