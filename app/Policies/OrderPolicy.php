<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Admins see every order, the customer sees their own, and drivers see the ones assigned
     * to them plus the unassigned ones still waiting for a driver.
     */
    public function view(User $user, Order $order): bool
    {
        return $user->isAdmin()
            || $order->customer_id === $user->id
            || ($user->isDriver() && ($order->driver_id === $user->id || $order->driver_id === null));
    }

    /**
     * Only admins or the assigned driver can move an order through its lifecycle.
     */
    public function updateStatus(User $user, Order $order): bool
    {
        return $user->isAdmin()
            || ($user->isDriver() && $order->driver_id === $user->id);
    }

    /**
     * Only admins can assign drivers.
     */
    public function assignDriver(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }
}
