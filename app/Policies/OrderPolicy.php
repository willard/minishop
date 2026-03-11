<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

/**
 * Authorization policy for Order management.
 *
 * Each method delegates to a Spatie permission check (e.g. 'orders.view').
 * Super-admins bypass all checks via Gate::before in AppServiceProvider.
 */
class OrderPolicy
{
    public function create(User $user): bool
    {
        return $user->can('orders.create');
    }

    public function viewAny(User $user): bool
    {
        return $user->can('orders.view');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->can('orders.view');
    }

    public function update(User $user, Order $order): bool
    {
        return $user->can('orders.update');
    }

    public function invoice(User $user, Order $order): bool
    {
        return $user->can('orders.invoice');
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->can('orders.delete');
    }
}
