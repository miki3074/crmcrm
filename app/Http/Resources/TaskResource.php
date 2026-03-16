<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request) {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status,
            'due_date' => $this->due_date,
            'responsibles' => $this->whenLoaded('responsibles', fn() => $this->responsibles->map->only('id', 'name')),
            // Добавляем флаг участия через подзадачи
            'user_in_subtasks' => $this->subtasks->contains(fn($s) =>
                $s->executors->contains('id', auth()->id()) || $s->responsibles->contains('id', auth()->id())
            ),
        ];
    }
}
