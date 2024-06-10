<?php

namespace Ceremonies\Models;
use Ceremonies\Core\Model;
use Illuminate\Support\Carbon;

class Payment extends Model
{

    protected $fillable = ['requestId', 'scpReference', 'state', 'transactionId', 'state'];

    protected $casts = [
        'created_at' => 'datetime:H:i:s - d/m/Y',
        'updated_at' => 'datetime:H:i:s - d/m/Y',
    ];

    public function getUpdatedAt() {
        return Carbon::createFromFormat('Y-m-d H:i:s', $this->updated_at)->format('H:i:s - d/m/Y');
    }

    public function getUser() {
        $this->user = get_user_by('id', $this->userId);
    }

    public function package()
    {
        return $this->belongsTo(Package::class, 'packageId', 'id');
    }

}