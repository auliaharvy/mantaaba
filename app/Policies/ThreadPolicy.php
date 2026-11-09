<?php

namespace App\Policies;

use App\Models\User;

/** PRD 8.7: penulis mengedit/menghapus miliknya; moderator menyembunyikan, mengunci, menyematkan. */
class ThreadPolicy extends OwnedPolicy
{
    protected string $module = 'thread';

    protected string $moderatorPermission = 'thread-moderate';

    public function view(User $user, $thread): bool
    {
        return parent::view($user, $thread) && ($thread->status === 'published' || $this->owns($user, $thread) || $this->moderates($user));
    }

    public function moderate(User $user): bool
    {
        return $this->moderates($user);
    }

    /** Thread terkunci atau tersembunyi tidak bisa dibalas. */
    public function reply(User $user, $thread): bool
    {
        return $user->can('comment-create') && $thread->status === 'published' && ! $thread->is_locked;
    }
}
