<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A customer enquiry. Product title/price are a snapshot at the time of the enquiry,
 * so sales see what the customer saw even if the catalogue changes later.
 */
class Enquiry extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'product_id', 'product_title', 'product_price',
        'name', 'email', 'phone', 'message',
        'status', 'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'product_id' => 'integer',
            'product_price' => 'decimal:2',
            'sent_at' => 'datetime',
        ];
    }
}
