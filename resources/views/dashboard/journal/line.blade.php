@php
    $amount = fn ($v) => $v !== null && (float) $v > 0 ? rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.') : '';
    $debit = $line['debit'] ?? (($line['side'] ?? null) === 'debit' ? '' : null);
@endphp
<tr>
    <td><select name="lines[{{ $i }}][coa_account_id]" class="form-select form-select-sm" data-account aria-label="Akun">
        <option value="">— Akun —</option>@foreach ($accounts as $a)<option value="{{ $a->id }}" @selected(($line['coa_account_id'] ?? null) == $a->id)>{{ $a->code }} {{ $a->name }}</option>@endforeach</select></td>
    <td><select name="lines[{{ $i }}][cost_center_id]" class="form-select form-select-sm" data-center aria-label="Pusat biaya">
        <option value="">—</option>@foreach ($centers as $c)<option value="{{ $c->id }}" @selected(($line['cost_center_id'] ?? null) == $c->id)>{{ $c->code }}</option>@endforeach</select></td>
    <td><select name="lines[{{ $i }}][party_id]" class="form-select form-select-sm" data-party aria-label="Pihak">
        <option value="">—</option>@foreach ($parties as $p)<option value="{{ $p->id }}" @selected(($line['party_id'] ?? null) == $p->id)>{{ $p->name }}</option>@endforeach</select></td>
    <td><input name="lines[{{ $i }}][memo]" value="{{ $line['memo'] ?? '' }}" class="form-control form-control-sm" aria-label="Memo"></td>
    <td><input name="lines[{{ $i }}][debit]" value="{{ $amount($line['debit'] ?? null) }}" type="number" step="0.01" min="0" class="form-control form-control-sm text-end" data-debit aria-label="Debit"></td>
    <td><input name="lines[{{ $i }}][credit]" value="{{ $amount($line['credit'] ?? null) }}" type="number" step="0.01" min="0" class="form-control form-control-sm text-end" data-credit aria-label="Kredit"></td>
    <td><button type="button" class="btn btn-sm btn-icon btn-light-danger" data-remove title="Hapus baris"><i class="fa fa-times"></i></button></td>
</tr>
