@if ($thread->status === 'hidden')<span class="badge badge-light-danger">Disembunyikan</span>@endif
@if ($thread->is_locked)<span class="badge badge-light-warning">Terkunci</span>@endif
@if ($thread->is_pinned)<span class="badge badge-light-primary">Disematkan</span>@endif
