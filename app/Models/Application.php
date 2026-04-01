<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    protected $fillable = [
        'student_id',
        'position_id',
        'siwes_duration',
        'status',
        'submitted_documents',
        'acceptance_deadline',
        'accepted_by_student_at',
    ];

    protected $casts = [
        'submitted_documents' => 'array',
        'acceptance_deadline' => 'datetime',
        'accepted_by_student_at' => 'datetime',
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