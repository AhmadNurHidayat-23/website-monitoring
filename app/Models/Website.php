<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Website extends Model
{
    protected $fillable = ['name', 'url', 'interval_minutes'];

    protected function casts(): array
    {
        return ['checked_at' => 'datetime', 'interval_minutes' => 'integer'];
    }

    public function checks(): HasMany
    {
        return $this->hasMany(Check::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'up' => 'Up', 'down' => 'Down', default => 'Belum Diperiksa',
        };
    }

    public function isDue(): bool
    {
        return $this->checked_at === null || $this->checked_at->addMinutes($this->interval_minutes)->isPast();
    }
}
