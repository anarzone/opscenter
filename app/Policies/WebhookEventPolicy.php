<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Auth\Access\HandlesAuthorization;

class WebhookEventPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user) {}

    public function view(User $user, WebhookEvent $webhookEvent) {}

    public function create(User $user) {}

    public function update(User $user, WebhookEvent $webhookEvent) {}

    public function delete(User $user, WebhookEvent $webhookEvent) {}

    public function restore(User $user, WebhookEvent $webhookEvent) {}

    public function forceDelete(User $user, WebhookEvent $webhookEvent) {}
}
