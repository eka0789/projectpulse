<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'assignee_id' => $this->assignee_id,
            'priority' => $this->priority,
            'status' => $this->status,
            'estimated_hours' => $this->estimated_hours,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'deadline' => $this->deadline?->format('Y-m-d'),
            'completed_at' => $this->completed_at?->toISOString(),
            'source' => $this->source,
            'total_logged_minutes' => $this->when(
                isset($this->total_logged_minutes),
                (int) ($this->total_logged_minutes ?? 0)
            ),
            'project' => new ProjectResource($this->whenLoaded('project')),
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'time_logs' => TimeLogResource::collection($this->whenLoaded('timeLogs')),
            'progress_notes' => ProgressNoteResource::collection($this->whenLoaded('progressNotes')),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
