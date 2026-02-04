<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DecisionValue extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function decisions()
    {
        return $this->hasMany(Decision::class, 'decision_value_id');
    }

    public function vettingRecommendations()
    {
        return $this->hasMany(VettingRecord::class, 'recommendation_id');
    }
}
