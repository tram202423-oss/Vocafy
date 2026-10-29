<?php

namespace App\Policies;

use App\Models\IeltsSection;
use App\Models\User;

class IeltsSectionPolicy
{
    public function viewAny(User $user): bool { return $user->hasAnyRole(['super-admin', 'admin', 'editor']); }
    public function view(User $user, IeltsSection $record): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $this->viewAny($user); }
    public function update(User $user, IeltsSection $record): bool { return $this->viewAny($user); }
    public function delete(User $user, IeltsSection $record): bool { return $user->isAdmin() && ! $record->submissions()->exists(); }
    public function deleteAny(User $user): bool { return false; }
}
