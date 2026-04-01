<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyProfile extends Model
{
    protected $fillable = [
        'user_id',
        'company_name',
        'industry',
        'state',
        'city',
        'website',
        'description',
        'past_projects',
        'linkedin_url',
        'instagram_url',
        'accommodation_available',
        'is_verified',
        'contact_email',
        'contact_phone',
        'contact_whatsapp',
    ];

    protected $casts = [
        'accommodation_available' => 'boolean',
        'is_verified' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function positions()
    {
        return $this->hasMany(Position::class, 'company_id');
    }
}