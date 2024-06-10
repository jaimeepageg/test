<?php

namespace Ceremonies\Models;

use Ceremonies\Core\Model;
class User extends Model
{

    protected $table = 'wp_users';

    public function payments() {
        return $this->hasMany(Payment::class, 'id', 'ID');
    }

}