<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgressNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'note',
        'status_snapshot',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
