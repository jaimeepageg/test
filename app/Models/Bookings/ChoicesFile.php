<?php

namespace Ceremonies\Models\Bookings;

use Ceremonies\Core\Model;

class ChoicesFile extends Model
{

    public $timestamps = false;
    protected $fillable = ['form_id', 'question_name', 'file_name', 'local_file_path', 'created_at'];

    public function choices()
    {
        return $this->belongsTo(Choices::class, 'id', 'form_id');
    }

	public function getPublicUrl() {
		return home_url() . '/wp-content/cer-files/' . $this->local_file_path;
	}
	
    public function getPublicData()
    {
        return [
            'id' => $this->id,
            'name' => $this->file_name,
            'type' => 'Document',
        ];
    }

}