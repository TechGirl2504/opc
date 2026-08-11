<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Institution;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'institution_id',
        'profile_picture',
        'is_active',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    // Relationships
    public function createdApplications()
    {
        return $this->hasMany(Application::class, 'created_by');
    }

    public function assignedPoliceApplications()
    {
        return $this->hasMany(Application::class, 'assigned_police_officer_id');
    }

    public function assignedNisApplications()
    {
        return $this->hasMany(Application::class, 'assigned_nis_officer_id');
    }

    public function assignedOpcApplications()
    {
        return $this->hasMany(Application::class, 'assigned_opc_approver_id');
    }

    public function vettingRecords()
    {
        return $this->hasMany(VettingRecord::class, 'conducted_by');
    }

    public function uploadedDocuments()
    {
        return $this->hasMany(Document::class, 'uploaded_by');
    }

    public function decisions()
    {
        return $this->hasMany(Decision::class, 'decided_by');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function pushSubscriptions()
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function unreadNotifications()
    {
        return $this->hasMany(Notification::class)->where('is_read', false);
    }

    // Institution relationship
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    // Helper method to get role name (for backward compatibility)
    public function getRoleAttribute()
    {
        return $this->roles->first()?->name;
    }
}
