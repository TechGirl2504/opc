<?php

namespace App\Events;

use App\Models\Application;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApplicationStatusChanged
{
    use Dispatchable, SerializesModels;

    public Application $application;
    public $oldStatusId;
    public $newStatusId;

    public function __construct(Application $application, $oldStatusId, $newStatusId)
    {
        $this->application = $application;
        $this->oldStatusId = $oldStatusId;
        $this->newStatusId = $newStatusId;
    }
}
