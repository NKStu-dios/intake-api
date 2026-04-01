<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    protected $fillable = [
        'company_id',
        'title',
        'department',
        'field_required',
        'slots_total',
        'slots_filled',
        'duration',
        'work_days',
        'work_type',
        'work_style',
        'mentor_available',
        'allowance_available',
        'allowance_amount',
        'requirements',
        'next_steps',
        'application_deadline',
        'acceptance_window_days',
        'status',
    ];

    protected $casts = [
        'mentor_available' => 'boolean',
        'allowance_available' => 'boolean',
        'next_steps' => 'array',
        'application_deadline' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(CompanyProfile::class, 'company_id');
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function savedByStudents()
    {
        return $this->hasMany(SavedPosition::class);
    }

    public function isFull(): bool
    {
        return $this->slots_filled >= $this->slots_total;
    }
}