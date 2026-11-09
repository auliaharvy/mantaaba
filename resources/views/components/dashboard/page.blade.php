{{-- Kerangka halaman dashboard modul baru: judul, breadcrumb, alert, DataTables, hapus via data-delete-url. --}}
@props(['title', 'crumbs' => []])
<x-dashboard.layout>
    <x-slot name="title">{{ $title }}</x-slot>
    <x-slot name="style">
        <link rel="stylesheet" href="{{ asset('plugins/custom/datatables/datatables.bundle.css') }}">
        {{ $style ?? '' }}
    </x-slot>

    <x-slot name="toolbar">
        <x-dashboard.toolbar.main>
            <x-slot name="title">{{ $title }}</x-slot>
            <x-slot name="breadcrumb">
                @foreach ($crumbs as $crumb)
                    @unless ($loop->first)
                        <li class="breadcrumb-item"><span class="bullet bg-gray-200 w-5px h-2px"></span></li>
                    @endunless
                    <li class="breadcrumb-item text-muted">{{ $crumb }}</li>
                @endforeach
            </x-slot>
        </x-dashboard.toolbar.main>
    </x-slot>

    <x-slot name="post">
        <x-dashboard.alert.success />
        <x-dashboard.alert.warning />
        <x-dashboard.alert.error />
        {{ $slot }}
    </x-slot>

    <x-slot name="javascript">
        <script src="{{ asset('plugins/custom/datatables/datatables.bundle.js') }}"></script>
        <script>
            $(document).on('click', '[data-delete-url]', function () {
                const url = $(this).data('delete-url');
                const text = $(this).data('confirm') || 'Data yang terhapus tidak dapat dikembalikan.';
                swal.fire({
                    title: 'Hapus data ini?', text: text, icon: 'warning', showCloseButton: true,
                    confirmButtonColor: '#f1416c', confirmButtonText: 'Ya, Hapus!'
                }).then(function (result) {
                    if (!result.value) return;
                    $.ajax({
                        url: url, type: 'POST', data: { _method: 'DELETE' },
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        success: function () { location.reload(); },
                        error: function (xhr) {
                            swal.fire('Maaf', (xhr.responseJSON && xhr.responseJSON.message) || 'Terjadi kesalahan', 'error');
                        }
                    });
                });
            });
        </script>
        {{ $javascript ?? '' }}
    </x-slot>
</x-dashboard.layout>
