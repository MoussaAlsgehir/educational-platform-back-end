<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $table='questions';
    protected $guarded = ['id'];

    public function quizz(){
        return $this->belongsTo(Quizz::class);
    }
    public function answers(){
        return $this->hasMany(Answer::class);
    }
}
