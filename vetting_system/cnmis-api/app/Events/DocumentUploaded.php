<?php

namespace App\Events;

use App\Models\Application;
use App\Models\Document;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentUploaded
{
    use Dispatchable, SerializesModels;

    public Application $application;
    public Document $document;

    public function __construct(Application $application, Document $document)
    {
        $this->application = $application;
        $this->document = $document;
    }
}
