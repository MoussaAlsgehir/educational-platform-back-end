<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Transaction extends Model
{
    protected $fillable = [
        'wallet_id', 'direction', 'transaction_type', 'amount', 'balance_after', 'description'
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }


        // فلترة حسب المستخدم (عبر علاقة المحفظة)
    public function scopeFilterByUser($query, $userId)
    {
        if ($userId) {
            return $query->whereHas('wallet', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
        }
        return $query;
    }

    // فلترة حسب نوع العملية
    public function scopeFilterByType($query, $type)
    {
        if ($type) return $query->where('transaction_type', $type);
        return $query;
    }

    // فلترة حسب الاتجاه (credit / debit)
    public function scopeFilterByDirection($query, $direction)
    {
        if ($direction) return $query->where('direction', $direction);
        return $query;
    }

    // فلترة حسب المبلغ
    public function scopeFilterByAmount($query, $min, $max)
    {
        if ($min !== null) $query->where('amount', '>=', $min);
        if ($max !== null) $query->where('amount', '<=', $max);
        return $query;
    }

    // فلترة حسب التاريخ
    public function scopeFilterByDateRange($query, $from, $to)
    {
        if ($from) $query->whereDate('created_at', '>=', $from);
        if ($to) $query->whereDate('created_at', '<=', $to);
        return $query;
    }
}
