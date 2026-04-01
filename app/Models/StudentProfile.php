<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentProfile extends Model
{
    protected $fillable = [
        'user_id',
        'full_name',
        'phone',
        'university',
        'department',
        'field_of_study',
        'current_level',
        'cgpa',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class, 'student_id');
    }

    public function savedPositions()
    {
        return $this->hasMany(SavedPosition::class, 'student_id');
    }
}