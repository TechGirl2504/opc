<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Application extends Model
{
    use HasFactory, SoftDeletes;

    protected $appends = [
        'allowed_actions',
    ];

    protected $fillable = [
        'application_number',
        'full_name',
        'national_id',
        'district',
        'traditional_authority',
        'village',
        'current_name',
        'requested_name',
        'reason',
        'status_id',
        'created_by',
        'assigned_police_officer_id',
        'assigned_nis_officer_id',
        'assigned_opc_approver_id',
        'submitted_at',
        'police_vetting_completed_at',
        'nis_vetting_completed_at',
        'decided_at',
        'approver_send_back_reason',
        'approver_send_back_at',
        'data_entry_return_reason',
        'data_entry_return_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'police_vetting_completed_at' => 'datetime',
        'nis_vetting_completed_at' => 'datetime',
        'decided_at' => 'datetime',
        'approver_send_back_at' => 'datetime',
        'data_entry_return_at' => 'datetime',
    ];

    /**
     * Generate unique application number
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($application) {
            if (empty($application->application_number)) {
                $application->application_number = $application->generateApplicationNumber();
            }
        });
    }

    /**
     * Helper to generate a unique application number.
     */
    public function generateApplicationNumber(): string
    {
        $currentYear = date('Y');

        // Get the last application number for the current year using DB facade
        $lastApplication = DB::table('applications')
            ->where('application_number', 'like', "CNMIS-{$currentYear}-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastApplication && preg_match('/CNMIS-\d{4}-(\d+)/', $lastApplication->application_number, $matches)) {
            $sequence = (int) $matches[1] + 1;
        } else {
            $sequence = 1;
        }

        return 'CNMIS-' . $currentYear . '-' . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }

    // Relationships
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedPoliceOfficer()
    {
        return $this->belongsTo(User::class, 'assigned_police_officer_id');
    }

    public function assignedNisOfficer()
    {
        return $this->belongsTo(User::class, 'assigned_nis_officer_id');
    }

    public function assignedOpcApprover()
    {
        return $this->belongsTo(User::class, 'assigned_opc_approver_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function vettingRecords()
    {
        return $this->hasMany(VettingRecord::class);
    }

    public function status()
    {
        return $this->belongsTo(ApplicationStatus::class, 'status_id');
    }

    public function policeVetting()
    {
        return $this->hasOne(VettingRecord::class)->whereHas('vettingType', function ($query) {
            $query->where('code', 'police');
        });
    }

    public function nisVetting()
    {
        return $this->hasOne(VettingRecord::class)->whereHas('vettingType', function ($query) {
            $query->where('code', 'nis');
        });
    }

    public function decisions()
    {
        return $this->hasMany(Decision::class);
    }

    public function latestDecision()
    {
        return $this->hasOne(Decision::class)->latestOfMany();
    }

    /**
     * Backend-defined allowed actions for the current authenticated user.
     *
     * The model exposes this so every API response can carry the same workflow
     * truth that the controllers enforce.
     */
    public function getAllowedActionsAttribute(): array
    {
        /** @var \App\Services\ApplicationService $applicationService */
        $applicationService = app(\App\Services\ApplicationService::class);

        return $applicationService->getAllowedActions($this, auth()->user());
    }
}
