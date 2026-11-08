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

    public function isUp(): bool
    {
        return $this->status === 'up';
    }

    public function isDown(): bool
    {
        return $this->status === 'down';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' || $this->status === null;
    }

    public function scopeUp($query)
    {
        return $query->where('status', 'up');
    }

    public function scopeDown($query)
    {
        return $query->where('status', 'down');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
