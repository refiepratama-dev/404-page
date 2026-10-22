<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class TicketType extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'description',
        'price',
        'quota',
        'sold',
        'sales_start',
        'sales_end',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'quota' => 'integer',
            'sold' => 'integer',
            'sales_start' => 'datetime',
            'sales_end' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getRemainingAttribute(): int
    {
        return max(0, $this->quota - $this->sold);
    }

    public function isSoldOut(): bool
    {
        return $this->remaining <= 0;
    }

    public function isOnSale(): bool
    {
        $now = now();

        if ($this->sales_start && $now->lt($this->sales_start)) {
            return false;
        }

        return ! ($this->sales_end && $now->gt($this->sales_end));
    }

    public function scopeOnSale(Builder $query): Builder
    {
        $now = now();

        return $query->where('quota', '>', DB::raw('sold'))
            ->where(fn ($q) => $q->whereNull('sales_start')->orWhere('sales_start', '<=', $now))
            ->where(fn ($q) => $q->whereNull('sales_end')->orWhere('sales_end', '>=', $now));
    }

    /**
     * Atomically reserve stock so concurrent checkouts cannot oversell.
     *
     * Returns false when the requested quantity no longer fits the remaining quota.
     */
    public function reserve(int $quantity): bool
    {
        if ($quantity < 1) {
            return false;
        }

        $affected = static::whereKey($this->getKey())
            ->whereRaw('sold + ? <= quota', [$quantity])
            ->update(['sold' => DB::raw('sold + '.$quantity)]);

        return $affected === 1;
    }

    /**
     * Give stock back, used when an order is cancelled.
     */
    public function release(int $quantity): void
    {
        if ($quantity < 1) {
            return;
        }

        // CASE WHEN keeps this portable across SQLite and MySQL.
        static::whereKey($this->getKey())
            ->update(['sold' => DB::raw('CASE WHEN sold - '.(int) $quantity.' < 0 THEN 0 ELSE sold - '.(int) $quantity.' END')]);
    }
}
