<div class="d-flex justify-content-end gap-2">
    @if (auth()->user()->can('managePermissions', $target))
        <a href="{{ route('user.permissions', $target) }}" class="btn btn-sm btn-icon btn-light-primary" title="Hak akses"><i class="fa fa-key"></i></a>
    @endif
    @can('update', $target)
        <a href="{{ route('user.edit', $target) }}" class="btn btn-sm btn-icon btn-light-success" title="Ubah"><i class="fa fa-pen"></i></a>
    @endcan
</div>
