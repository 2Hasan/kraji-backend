<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['service_record_id', 'storage_key', 'mime_type', 'size_bytes'])]
class ServiceRecordAttachment extends Model
{
    protected $table = 'service_record_attachments';

    protected $appends = ['url'];

    public function getUrlAttribute(): string
    {
        return \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'attachments.show',
            now()->addMinutes(15),
            ['attachment' => $this->id],
        );
    }
}
