<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Learner extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'learner_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'course',
        'enrollment_status',
    ];
}
