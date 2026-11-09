<x-dashboard.page :title="'Kepengurusan ' . $period->name" :crumbs="['Organisasi', 'Kepengurusan', 'Rincian']">
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-5">
        <a class="btn btn-sm btn-light" href="{{ route('board.index', ['organization_id' => $period->organization_id]) }}"><i class="fa fa-arrow-left"></i> Daftar periode</a>
        @can('create', App\Models\BoardPeriod::class)
            <form method="POST" action="{{ route('board.duplicate', $period) }}">
                @csrf
                <button class="btn btn-sm btn-light-info" type="submit"><i class="fa fa-copy"></i> Buat periode baru dari periode ini</button>
            </form>
        @endcan
        @can('update', $period)
            <a class="btn btn-sm btn-light-success" href="{{ route('board.edit', $period) }}"><i class="fa fa-pen"></i> Ubah periode</a>
        @endcan
    </div>

    <div class="row g-5">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <div class="card-title fs-4 fw-bolder">{{ $period->organization->name }}</div>
                    <div class="card-toolbar">
                        <span class="badge {{ $period->is_active ? 'badge-light-success' : 'badge-light' }}">{{ $period->is_active ? 'Periode aktif' : 'Tidak aktif' }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="text-muted mb-4">
                        {{ $period->starts_at?->translatedFormat('j M Y') ?? '?' }} – {{ $period->ends_at?->translatedFormat('j M Y') ?? '?' }}
                        @if ($period->sk_number) · SK {{ $period->sk_number }} @endif
                        @if ($period->sk_file) · <a href="{{ Storage::disk('public')->url($period->sk_file) }}" target="_blank">Lihat SK</a> @endif
                    </div>
                    @include('dashboard.board.members-list', ['manage' => auth()->user()->can('update', $period)])
                </div>
            </div>
        </div>

        @can('update', $period)
            <div class="col-lg-5">
                <form method="POST" action="{{ route('board-member.store', $period) }}" class="card">
                    @csrf
                    <div class="card-header"><div class="card-title fs-4 fw-bolder">Tambah Pengurus</div></div>
                    <div class="card-body">
                        <div class="mb-6">
                            <label class="form-label required" for="board-user">Anggota</label>
                            <select class="form-select @error('user_id') is-invalid @enderror" id="board-user" name="user_id" required
                                data-placeholder="Ketik nama anggota"></select>
                            @error('user_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <x-dashboard.field name="position_id" label="Jabatan" type="select" :options="$positions" required
                            help="Jabatan inti (Ketua, Sekretaris, ...) atau jabatan bidang sesuai tingkat organisasi. Kelola daftar di menu Divisi & Jabatan." />
                        <x-dashboard.field name="sort_order" label="Urutan dalam jabatan" type="number" min="0" :value="0" />
                    </div>
                    <div class="card-footer text-end"><button class="btn btn-sm btn-primary" type="submit">Tambah</button></div>
                </form>
            </div>
        @endcan
    </div>

    <x-slot name="javascript">
        <script>
            $('#board-user').select2({
                minimumInputLength: 2,
                ajax: {
                    url: '{{ route('board.candidates') }}', dataType: 'json', delay: 250,
                    data: (params) => ({ q: params.term }),
                    processResults: (data) => ({ results: data })
                },
                language: { inputTooShort: () => 'Ketik minimal 2 huruf', noResults: () => 'Tidak ditemukan' }
            });
        </script>
    </x-slot>
</x-dashboard.page>
