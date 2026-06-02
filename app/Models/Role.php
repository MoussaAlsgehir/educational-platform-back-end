<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $guarded = ['id'];
    protected $able = 'roles';

    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user');
    }
}
