{{-- Tombol aksi baris tabel; kirim null untuk menyembunyikan tombol. --}}
@props(['show' => null, 'edit' => null, 'delete' => null, 'confirm' => null])
<div class="d-flex justify-content-end gap-2">
    @if ($show)
        <a href="{{ $show }}" class="btn btn-sm btn-icon btn-light-warning" title="Rincian"><i class="fa fa-search"></i></a>
    @endif
    @if ($edit)
        <a href="{{ $edit }}" class="btn btn-sm btn-icon btn-light-success" title="Ubah"><i class="fa fa-pen"></i></a>
    @endif
    @if ($delete)
        <button type="button" class="btn btn-sm btn-icon btn-light-danger" title="Hapus" data-delete-url="{{ $delete }}"
            @if ($confirm) data-confirm="{{ $confirm }}" @endif><i class="fa fa-trash"></i></button>
    @endif
</div>
