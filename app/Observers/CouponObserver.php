<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Coupon;
use Illuminate\Support\Facades\Auth;

class CouponObserver
{
    public function created(Coupon $coupon): void
    {
        ActivityLog::query()->create([
            'user_id' => Auth::id(),
            'action' => 'created',
            'subject_type' => 'Coupon',
            'subject_id' => $coupon->id,
            'description' => "Created coupon \"{$coupon->code}\"",
            'properties' => null,
        ]);
    }

    public function updated(Coupon $coupon): void
    {
        $changed = $coupon->getChanges();
        unset($changed['updated_at']);

        if (empty($changed)) {
            return;
        }

        ActivityLog::query()->create([
            'user_id' => Auth::id(),
            'action' => 'updated',
            'subject_type' => 'Coupon',
            'subject_id' => $coupon->id,
            'description' => "Updated coupon \"{$coupon->code}\"",
            'properties' => $changed,
        ]);
    }

    public function deleted(Coupon $coupon): void
    {
        ActivityLog::query()->create([
            'user_id' => Auth::id(),
            'action' => 'deleted',
            'subject_type' => 'Coupon',
            'subject_id' => $coupon->id,
            'description' => "Deleted coupon \"{$coupon->code}\"",
            'properties' => null,
        ]);
    }
}
