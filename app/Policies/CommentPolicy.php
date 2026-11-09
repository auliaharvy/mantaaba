<?php

namespace App\Policies;

class CommentPolicy extends OwnedPolicy
{
    protected string $module = 'comment';

    protected string $moderatorPermission = 'thread-moderate';
}
