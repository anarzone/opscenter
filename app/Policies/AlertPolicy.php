<?php

namespace App\Policies;

use App\Models\Alert;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AlertPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user) {}

    public function view(User $user, Alert $alert) {}

    public function create(User $user) {}

    public function update(User $user, Alert $alert) {}

    public function delete(User $user, Alert $alert) {}

    public function restore(User $user, Alert $alert) {}

    public function forceDelete(User $user, Alert $alert) {}
}
