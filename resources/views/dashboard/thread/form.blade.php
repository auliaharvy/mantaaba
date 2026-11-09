@php($editing = $thread->exists)
<x-dashboard.page :title="$editing ? 'Ubah Diskusi' : 'Diskusi Baru'" :crumbs="['Postingan', 'Forum', $editing ? 'Ubah' : 'Baru']">
    <form method="POST" action="{{ $editing ? route('thread.update', $thread) : route('thread.store') }}" class="card">
        @csrf @if ($editing) @method('PATCH') @endif
        <div class="card-header">
            <div class="card-title fs-3 fw-bolder">{{ $editing ? $thread->title : 'Mulai diskusi' }}</div>
            <div class="card-toolbar"><a class="btn btn-sm btn-light-primary" href="{{ route('thread.index') }}"><i class="fa fa-arrow-left"></i> Kembali</a></div>
        </div>
        <div class="card-body">
            <x-dashboard.field name="title" label="Judul" :value="$thread->title" required />
            <x-dashboard.field name="forum_category_id" label="Kategori" type="select" :options="$categories" :value="$thread->forum_category_id" placeholder="— Umum —" />
            <div class="mb-6">
                <label class="form-label required" for="body">Isi</label>
                <textarea id="body" name="body">{{ old('body', $thread->body) }}</textarea>
                @error('body')<div class="text-danger fs-7">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="card-footer text-end"><button class="btn btn-sm btn-primary" type="submit">{{ $editing ? 'Simpan' : 'Terbitkan' }}</button></div>
    </form>
    <x-slot name="javascript">@include('dashboard.partials.tinymce')</x-slot>
</x-dashboard.page>
