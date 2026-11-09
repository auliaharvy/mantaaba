<form method="POST" action="{{ route('membership.suspend', $member) }}" class="d-flex justify-content-end gap-2"
    onsubmit="return confirm('Ubah status {{ e($member->user->name) }} menjadi SKORS?')">
    @csrf @method('PATCH')
    <a href="{{ route('membership.show', $member) }}" class="btn btn-sm btn-icon btn-light-warning" title="Rincian"><i class="fa fa-search"></i></a>
    <button class="btn btn-sm btn-light-danger" type="submit">Skors</button>
</form>
