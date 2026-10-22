<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'order_number',
        'event_id',
        'buyer_name',
        'buyer_email',
        'buyer_phone',
        'notes',
        'total',
        'status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getRouteKeyName(): string
    {
        return 'order_number';
    }

    public static function generateNumber(): string
    {
        do {
            $number = 'EVT-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (static::where('order_number', $number)->exists());

        return $number;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'Lunas',
            self::STATUS_CANCELLED => 'Dibatalkan',
            default => 'Menunggu',
        };
    }

    public function getTotalTicketsAttribute(): int
    {
        return (int) $this->items->sum('quantity');
    }
}
