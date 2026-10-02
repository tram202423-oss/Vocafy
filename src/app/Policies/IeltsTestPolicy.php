<?php

namespace App\Policies;

use App\Models\IeltsTest;
use App\Models\User;

class IeltsTestPolicy
{
    public function viewAny(User $user): bool { return $user->hasAnyRole(['super-admin', 'admin', 'editor']); }
    public function view(User $user, IeltsTest $record): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $this->viewAny($user); }
    public function update(User $user, IeltsTest $record): bool { return $this->viewAny($user); }
    public function delete(User $user, IeltsTest $record): bool { return $user->isAdmin() && ! $record->submissions()->exists(); }
    public function deleteAny(User $user): bool { return false; }
}
