<?php

namespace App\Policies;

use App\Models\IeltsSubmission;
use App\Models\User;

class IeltsSubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->checkPermissionTo('ielts.grade');
    }

    public function view(User $user, IeltsSubmission $submission): bool
    {
        return $user->isAdmin() || ($user->checkPermissionTo('ielts.grade')
            && (string) $submission->assigned_examiner_id === (string) $user->id
            && in_array($submission->skill, ['writing', 'speaking'], true)
            && $submission->status->value === 'completed');
    }

    public function update(User $user, IeltsSubmission $submission): bool
    {
        return $this->view($user, $submission)
            && ($user->isAdmin() || $submission->status->value === 'completed');
    }

    public function create(User $user): bool { return false; }
    public function delete(User $user, IeltsSubmission $submission): bool { return false; }
    public function deleteAny(User $user): bool { return false; }
}
