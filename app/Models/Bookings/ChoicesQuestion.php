<?php

namespace Ceremonies\Models\Bookings;

use Ceremonies\Core\Model;

class ChoicesQuestion extends Model {

	public $timestamps = false;
	protected $fillable = ['form_id', 'question', 'name', 'position'];

	public function choices() {
		$this->belongsTo(Choices::class, 'id', 'form_id');
	}

}