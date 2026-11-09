<x-dashboard.page :title="'Hak Akses: ' . $target->name" :crumbs="['Sistem', 'Pengguna', 'Hak Akses']">
    <form method="POST" action="{{ route('user.permissions.update', $target) }}" class="card">
        @csrf @method('PUT')
        <div class="card-header">
            <div class="card-title flex-column">
                <span class="fs-3 fw-bolder">{{ $target->name }}</span>
                <span class="text-muted fs-7"><x-dashboard.role-badge :user="$target" /> {{ $target->email }}</span>
            </div>
            <div class="card-toolbar"><a class="btn btn-sm btn-light" href="{{ route('user.edit', $target) }}"><i class="fa fa-arrow-left"></i> Kembali</a></div>
        </div>
        <div class="card-body">
            <div class="mb-6">
                <div class="fw-bold mb-2">Paket cepat <span class="text-muted fw-normal fs-7">(menambahkan centang; simpan untuk menerapkan)</span></div>
                <div class="d-flex flex-wrap gap-2">
                    @foreach ($packages as $key => $package)
                        <button type="button" class="btn btn-sm btn-light-primary" data-package='@json($package['permissions'])'>{{ $package['label'] }}</button>
                    @endforeach
                    <button type="button" class="btn btn-sm btn-light-danger" data-package-clear>Kosongkan semua</button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-row-bordered align-middle gy-3">
                    <thead><tr class="fw-bolder text-muted"><th>Modul</th><th>Akses</th></tr></thead>
                    <tbody>
                        @foreach (config('mantaaba.modules') as $module => $def)
                            <tr>
                                <td class="w-lg-300px">
                                    <div class="fw-bold">{{ $def['label'] }}</div>
                                    <a href="#" class="fs-7" data-module-all="{{ $module }}">pilih semua</a>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-5">
                                        @foreach (App\Support\Permissions::forModule($module) as $permission => $label)
                                            <label class="form-check form-check-sm form-check-custom">
                                                <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission }}" data-module="{{ $module }}" @checked(in_array($permission, old('permissions', $granted)))>
                                                <span class="form-check-label">{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-end"><button class="btn btn-sm btn-primary" type="submit">Simpan hak akses</button></div>
    </form>
    <x-slot name="javascript">
        <script>
            const boxes = () => document.querySelectorAll('input[name="permissions[]"]');
            document.querySelectorAll('[data-package]').forEach(btn => btn.addEventListener('click', () => {
                const set = new Set(JSON.parse(btn.dataset.package));
                boxes().forEach(box => { if (set.has(box.value)) box.checked = true; });
            }));
            document.querySelector('[data-package-clear]').addEventListener('click', () => boxes().forEach(box => box.checked = false));
            document.querySelectorAll('[data-module-all]').forEach(link => link.addEventListener('click', e => {
                e.preventDefault();
                document.querySelectorAll(`[data-module="${link.dataset.moduleAll}"]`).forEach(box => box.checked = true);
            }));
        </script>
    </x-slot>
</x-dashboard.page>
