<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverTelemetry extends Model
{
    use HasFactory;

    protected $table = 'driver_telemetries';

    protected $fillable = [
        'driver_id',
        'order_id',
        'latitude',
        'longitude',
        'altitude',
        'speed',
        'battery_level',
        'recorded_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'altitude' => 'decimal:2',
        'speed' => 'decimal:2',
        'battery_level' => 'integer',
        'recorded_at' => 'datetime',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
