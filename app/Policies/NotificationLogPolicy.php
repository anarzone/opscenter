<?php

namespace App\Policies;

use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NotificationLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user) {}

    public function view(User $user, NotificationLog $notificationLog) {}

    public function create(User $user) {}

    public function update(User $user, NotificationLog $notificationLog) {}

    public function delete(User $user, NotificationLog $notificationLog) {}

    public function restore(User $user, NotificationLog $notificationLog) {}

    public function forceDelete(User $user, NotificationLog $notificationLog) {}
}
