<?php

namespace App\Events;

use App\Models\Application;
use App\Models\VettingRecord;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VettingCompleted
{
    use Dispatchable, SerializesModels;

    public Application $application;
    public VettingRecord $vettingRecord;
    public string $vettingType;

    public function __construct(Application $application, VettingRecord $vettingRecord, string $vettingType)
    {
        $this->application = $application;
        $this->vettingRecord = $vettingRecord;
        $this->vettingType = $vettingType;
    }
}
