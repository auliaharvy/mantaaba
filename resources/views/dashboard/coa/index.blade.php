@php
    $byId = $accounts->keyBy('id');
    $depth = function ($account) use (&$depth, $byId) {
        return $account->parent_id && isset($byId[$account->parent_id]) ? $depth($byId[$account->parent_id]) + 1 : 0;
    };
@endphp
<x-dashboard.page title="Bagan Akun (COA)" :crumbs="['Keuangan', 'COA & Master']">
    @include('dashboard.coa.tabs', ['active' => 'coa'])
    <div class="card">
        <div class="card-header">
            <div class="card-title fs-4 fw-bolder">Bagan Akun · ISAK 35</div>
            @can('coa-create')<div class="card-toolbar"><a href="{{ route('coa.create') }}" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> Tambah Akun</a></div>@endcan
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-row-bordered align-middle gs-5 gy-2 mb-0 fs-7">
                    <thead><tr class="fw-bold bg-light"><th>Kode</th><th>Nama</th><th>Tipe</th><th>Saldo normal</th><th>Atribut</th><th class="text-end">Baris jurnal</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($accounts as $a)
                            <tr class="{{ $a->is_header ? 'fw-bolder bg-light-primary bg-opacity-50' : '' }} {{ $a->is_active ? '' : 'text-muted' }}">
                                <td class="text-nowrap">{{ $a->code }}</td>
                                <td style="padding-left: {{ 0.75 + $depth($a) * 1.25 }}rem">{{ $a->name }}@unless ($a->is_active) <span class="badge badge-light">nonaktif</span>@endunless</td>
                                <td>{{ App\Models\CoaAccount::TYPES[$a->type] }}</td>
                                <td>{{ ucfirst($a->normal_balance) }}</td>
                                <td>@foreach (['is_header' => 'header', 'is_cash' => 'kas', 'is_bank' => 'bank', 'is_receivable' => 'piutang', 'is_payable' => 'utang', 'is_contra' => 'kontra'] as $flag => $label)@if ($a->{$flag})<span class="badge badge-light me-1">{{ $label }}</span>@endif @endforeach
                                    @if ($a->restriction)<span class="badge badge-light-info">{{ $a->restriction === 'dengan_pembatasan' ? 'terikat' : 'tidak terikat' }}</span>@endif</td>
                                <td class="text-end">{{ $a->lines_count ?: '' }}</td>
                                <td class="text-end text-nowrap">
                                    @if ($a->is_header)@can('coa-create')<a href="{{ route('coa.create', ['parent' => $a->id]) }}" class="btn btn-sm btn-icon btn-light" title="Tambah sub-akun"><i class="fa fa-plus"></i></a>@endcan @endif
                                    @can('coa-update')<a href="{{ route('coa.edit', $a) }}" class="btn btn-sm btn-icon btn-light-success" title="Ubah"><i class="fa fa-pen"></i></a>@endcan
                                    @if (! $a->lines_count)@can('coa-delete')<button type="button" class="btn btn-sm btn-icon btn-light-danger" data-delete-url="{{ route('coa.destroy', $a) }}" title="Hapus"><i class="fa fa-trash"></i></button>@endcan @endif
                                    @if (! $a->is_header)@can('finance-report-view')<a href="{{ route('ledger.index', ['account' => $a->id]) }}" class="btn btn-sm btn-icon btn-light-primary" title="Buku besar"><i class="fa fa-book"></i></a>@endcan @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-dashboard.page>
