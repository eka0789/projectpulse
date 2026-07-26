<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AITaskGeneration extends Model
{
    use HasFactory;

    protected $table = 'ai_task_generations';

    protected $fillable = [
        'project_id',
        'requested_by',
        'provider',
        'model',
        'brief_hash',
        'request_payload',
        'response_payload',
        'status',
        'error_code',
        'error_message',
        'latency_ms',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'latency_ms' => 'integer',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
