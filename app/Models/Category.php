<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{

public function courses()
    {
        return $this->belongsToMany(Course::class, 'category_course');
    }
}
