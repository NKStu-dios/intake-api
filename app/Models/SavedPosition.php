<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedPosition extends Model
{
    protected $fillable = [
        'student_id',
        'position_id',
    ];

    public function student()
    {
        return $this->belongsTo(StudentProfile::class, 'student_id');
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }
}