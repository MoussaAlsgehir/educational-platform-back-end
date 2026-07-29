<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseCategory extends Model
{
    protected $table = 'category_course';
    protected $guarded = ['id'];


    public function course()
    {
        return $this->belongsTo(Course::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
