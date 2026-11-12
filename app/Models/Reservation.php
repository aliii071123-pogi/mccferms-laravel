<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Reservation extends Model
{
    protected $fillable = [
        'user_id', 'facility_id', 'starts_at', 'ends_at', 'pax', 'purpose', 'description',
        'status', 'reviewed_by', 'reviewed_at', 'return_due_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'return_due_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function equipment(): BelongsToMany
    {
        return $this->belongsToMany(Equipment::class, 'reservation_equipment')->withPivot('quantity');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ReservationSchedule::class)->orderBy('starts_at');
    }

    public function scheduleBlocks(): Collection
    {
        $schedules = $this->relationLoaded('schedules')
            ? $this->getRelation('schedules')
            : $this->schedules()->get();

        return $schedules->isNotEmpty()
            ? $schedules
            : collect([(object) ['starts_at' => $this->starts_at, 'ends_at' => $this->ends_at]]);
    }
}
