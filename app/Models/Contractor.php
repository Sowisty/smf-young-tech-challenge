<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contractor extends Model
{
    protected $guarded = [];

    /**
     * Relacja do faktur (kontrahent może mieć wiele faktur).
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}