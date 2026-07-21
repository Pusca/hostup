<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OwnerLead extends Model
{
    protected $guarded = ['id'];

    public const STATUSES = [
        'new' => 'Nuova',
        'contacted' => 'Contattato',
        'closed' => 'Chiusa',
    ];

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
