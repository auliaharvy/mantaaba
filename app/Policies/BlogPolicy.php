<?php

namespace App\Policies;

use App\Models\User;

/** PRD 9.1.4: penulis menyimpan draf; pemegang news-publish menerbitkan; penulis hanya mengedit drafnya. */
class BlogPolicy extends OwnedPolicy
{
    protected string $module = 'news';

    protected string $moderatorPermission = 'news-publish';

    public function view(User $user, $blog): bool
    {
        return parent::view($user, $blog) && ($blog->status === 'published' || $this->owns($user, $blog) || $this->moderates($user));
    }

    public function update(User $user, $blog): bool
    {
        return parent::update($user, $blog) && ($this->moderates($user) || $blog->status !== 'published');
    }

    public function delete(User $user, $blog): bool
    {
        return parent::delete($user, $blog) && ($this->moderates($user) || $blog->status !== 'published');
    }

    /** B5/8.6: hanya penerbit yang menentukan status terbit. */
    public function publish(User $user): bool
    {
        return $this->moderates($user);
    }
}
