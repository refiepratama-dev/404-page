<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_FINISHED = 'finished';

    public const STATUS_CANCELLED = 'cancelled';

    public const CATEGORIES = [
        'Konser Musik',
        'Tech & Startup',
        'Bisnis',
        'Seni & Budaya',
        'Olahraga',
        'Komunitas',
    ];

    /**
     * Semantic accent keys. The Blade layer maps each key to complete Tailwind
     * class strings, because Tailwind cannot see dynamically built class names.
     */
    public const ACCENTS = ['violet', 'sky', 'emerald', 'amber', 'rose', 'cyan'];

    protected $fillable = [
        'venue_id',
        'title',
        'slug',
        'category',
        'description',
        'banner_url',
        'accent',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Event $event) {
            if (blank($event->slug)) {
                $event->slug = static::uniqueSlug($event->title, $event->id);
            }
        });
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'event';
        $slug = $base;
        $suffix = 2;

        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(EventSession::class)->orderBy('sort_order')->orderBy('starts_at');
    }

    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class)->orderBy('price');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function orderItems(): HasManyThrough
    {
        return $this->hasManyThrough(OrderItem::class, Order::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('ends_at', '>=', now());
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $like = '%'.str_replace('%', '\%', $term).'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('title', 'like', $like)
                ->orWhere('category', 'like', $like)
                ->orWhere('description', 'like', $like);
        });
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isSoldOut(): bool
    {
        return $this->quota_total > 0 && $this->quota_total <= $this->quota_sold;
    }

    public function getQuotaTotalAttribute(): int
    {
        return (int) $this->ticketTypes()->sum('quota');
    }

    public function getQuotaSoldAttribute(): int
    {
        return (int) $this->ticketTypes()->sum('sold');
    }

    public function getMinPriceAttribute(): int
    {
        return (int) ($this->ticketTypes()->min('price') ?? 0);
    }

    public function getSoldPercentAttribute(): int
    {
        $total = $this->quota_total;

        return $total > 0 ? (int) round($this->quota_sold / $total * 100) : 0;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PUBLISHED => 'Terbit',
            self::STATUS_FINISHED => 'Selesai',
            self::STATUS_CANCELLED => 'Dibatalkan',
            default => 'Draf',
        };
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
