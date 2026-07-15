<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invoice extends Model
{
    protected $guarded = [];

    /**
     * Relacja do kontrahenta (każda faktura należy do jednego kontrahenta).
     */
    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    /**
     * Relacja do pozycji na fakturze (faktura może mieć wiele pozycji).
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    /**
     * Relacja do płatności (faktura ma przypisaną jedną informację o płatności).
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}