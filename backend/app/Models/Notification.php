<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $appends = [
        'data',
    ];

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'related_model_type',
        'related_model_id',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getDataAttribute(): array
    {
        if ($this->related_model_type === Application::class && $this->related_model_id) {
            return [
                'application_id' => $this->related_model_id,
            ];
        }

        return [];
    }
}
