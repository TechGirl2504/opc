<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Decision extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'decision_type_id',
        'decision_value_id',
        'decided_by',
        'denial_reason',
        'conditions',
        'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    // Relationships
    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function decidedBy()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function decisionType()
    {
        return $this->belongsTo(DecisionType::class, 'decision_type_id');
    }

    public function decisionValue()
    {
        return $this->belongsTo(DecisionValue::class, 'decision_value_id');
    }
}
