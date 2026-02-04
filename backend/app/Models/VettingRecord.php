<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VettingRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'vetting_type_id',
        'conducted_by',
        'status_id',
        'remarks',
        'findings',
        'recommendation_id',
        'vetting_date',
        'completed_at',
        'return_reason',
    ];

    protected $casts = [
        'vetting_date' => 'date',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function conductedBy()
    {
        return $this->belongsTo(User::class, 'conducted_by');
    }

    public function vettingType()
    {
        return $this->belongsTo(VettingType::class, 'vetting_type_id');
    }

    public function status()
    {
        return $this->belongsTo(VettingStatus::class, 'status_id');
    }

    public function recommendation()
    {
        return $this->belongsTo(DecisionValue::class, 'recommendation_id');
    }
}
