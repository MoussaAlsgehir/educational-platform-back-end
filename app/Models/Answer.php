<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $question_id
 * @property string $answer_text
 * @property int $is_correct
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Question $question
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Answer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Answer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Answer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Answer whereAnswerText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Answer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Answer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Answer whereIsCorrect($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Answer whereQuestionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Answer whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Answer extends Model
{
    protected $table ='answers';
    protected $guarded = ['id'];

    public function question(){
        return $this->belongsTo(Question::class);
    }
}
