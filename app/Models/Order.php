<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_id',
        'driver_id',
        'customer_name',
        'customer_phone',
        'delivery_address',
        'delivery_latitude',
        'delivery_longitude',
        'status',
        'payment_method',
        'payment_status',
        'subtotal',
        'delivery_fee',
        'total',
        'estimated_delivery_minutes',
        'estimated_delivery_at',
        'notes',
    ];

    protected $casts = [
        'delivery_latitude' => 'decimal:7',
        'delivery_longitude' => 'decimal:7',
        'subtotal' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'total' => 'decimal:2',
        'estimated_delivery_minutes' => 'integer',
        'estimated_delivery_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function telemetries(): HasMany
    {
        return $this->hasMany(DriverTelemetry::class);
    }

    public function latestTelemetry()
    {
        return $this->hasOne(DriverTelemetry::class)->latestOfMany();
    }
}
