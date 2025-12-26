<?php

namespace App\Events;

use App\Models\Application;
use App\Models\Decision;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApplicationApproved
{
    use Dispatchable, SerializesModels;

    public Application $application;
    public Decision $decision;

    public function __construct(Application $application, Decision $decision)
    {
        $this->application = $application;
        $this->decision = $decision;
    }
}
