<?php

namespace App\Providers;

use App\Events\ApplicationCreated;
use App\Events\ApplicationStatusChanged;
use App\Events\VettingCompleted;
use App\Events\ApplicationApproved;
use App\Events\ApplicationDenied;
use App\Events\DocumentUploaded;
use App\Listeners\SendNotificationListener;
use App\Listeners\CreateAuditLogListener;
use App\Listeners\UpdateApplicationStatusListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        ApplicationCreated::class => [
            CreateAuditLogListener::class,
        ],
        ApplicationStatusChanged::class => [
            CreateAuditLogListener::class,
            SendNotificationListener::class,
        ],
        VettingCompleted::class => [
            CreateAuditLogListener::class,
            SendNotificationListener::class,
            UpdateApplicationStatusListener::class,
        ],
        ApplicationApproved::class => [
            CreateAuditLogListener::class,
            SendNotificationListener::class,
        ],
        ApplicationDenied::class => [
            CreateAuditLogListener::class,
            SendNotificationListener::class,
        ],
        DocumentUploaded::class => [
            CreateAuditLogListener::class,
            SendNotificationListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

