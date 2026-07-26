<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

class TenantOwnedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->company_id > 0;
    }

    public function view(User $user, Model $resource): Response
    {
        return $this->owns($user, $resource);
    }

    public function create(User $user): bool
    {
        return $user->company_id > 0;
    }

    public function update(User $user, Model $resource): Response
    {
        return $this->owns($user, $resource);
    }

    public function delete(User $user, Model $resource): Response
    {
        return $this->owns($user, $resource);
    }

    public function restore(User $user, Model $resource): Response
    {
        return $this->owns($user, $resource);
    }

    public function forceDelete(User $user, Model $resource): Response
    {
        return $this->owns($user, $resource);
    }

    private function owns(User $user, Model $resource): Response
    {
        return (int) $resource->getAttribute('company_id') === $user->company_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
