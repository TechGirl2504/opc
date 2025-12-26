<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'max_file_size',
        'allowed_mime_types',
        'is_active',
    ];

    protected $casts = [
        'max_file_size' => 'integer',
        'allowed_mime_types' => 'array',
        'is_active' => 'boolean',
    ];

    public function documents()
    {
        return $this->hasMany(Document::class, 'document_type_id');
    }
}
