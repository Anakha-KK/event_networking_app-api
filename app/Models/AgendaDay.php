<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgendaDay extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'day_number',
        'date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_number' => 'integer',
            'date' => 'date',
        ];
    }

    public function slots(): HasMany
    {
        return $this->hasMany(AgendaSlot::class)->orderBy('start_time');
    }
}
