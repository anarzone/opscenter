<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WebhookSource;
use Illuminate\Auth\Access\HandlesAuthorization;

class WebhookSourcePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {

    }

    public function view(User $user, WebhookSource $webhookSource)
    {
    }

    public function create(User $user)
    {
    }

    public function update(User $user, WebhookSource $webhookSource)
    {
    }

    public function delete(User $user, WebhookSource $webhookSource)
    {
    }

    public function restore(User $user, WebhookSource $webhookSource)
    {
    }

    public function forceDelete(User $user, WebhookSource $webhookSource)
    {
    }
}
