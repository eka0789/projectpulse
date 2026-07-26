<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'company',
        'email',
        'phone',
        'address',
        'notes',
        'created_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}
