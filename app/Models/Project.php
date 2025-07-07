<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'title',
        'description',
        'image',
        'technologies',
        'category',
        'status',
        'demo_url',
        'github_url',
        'show_github'
    ];

    protected $casts = [
        'technologies' => 'array',
        'show_github' => 'boolean'
    ];
}
