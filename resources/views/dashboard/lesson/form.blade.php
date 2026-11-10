@php($editing = $lesson->exists)
<x-dashboard.page :title="$editing ? 'Ubah Materi' : 'Tambah Materi'" :crumbs="['Pembelajaran', $module->skema->code, $module->title]">
    <form method="POST" action="{{ $editing ? route('lesson.update', $lesson) : route('lesson.store', $module) }}" enctype="multipart/form-data" class="card">
        @csrf @if ($editing) @method('PATCH') @endif
        <div class="card-header"><div class="card-title fs-4 fw-bolder">{{ $module->skema->code }} · {{ $module->title }}</div></div>
        <div class="card-body row">
            <div class="col-md-8"><x-dashboard.field name="title" label="Judul materi" :value="$lesson->title" required /></div>
            <div class="col-md-4"><x-dashboard.field name="type" label="Jenis" type="select" :options="App\Models\Lesson::TYPES" :value="$lesson->type" required help="Tugas: siswa mengunggah file/jawaban, mentor menilai." /></div>
            <div class="col-12 mb-6"><label class="form-label" for="body">Isi / instruksi</label><textarea id="body" name="body">{{ old('body', $lesson->body) }}</textarea></div>
            <div class="col-md-6"><x-dashboard.field name="video_url" label="URL video YouTube (jenis Video)" :value="$lesson->video_url" placeholder="https://www.youtube.com/watch?v=..." /></div>
            <div class="col-md-6">
                <x-dashboard.field name="file" label="File materi (maks. 20 MB)" type="file" />
                @if ($lesson->file)<a href="{{ route('learn.file', $lesson) }}" class="fs-7 d-block mb-6">Unduh file saat ini</a>@endif
            </div>
            <div class="col-md-4"><x-dashboard.field name="due_days" label="Tenggat tugas (hari setelah skema dibuka)" type="number" min="1" :value="$lesson->due_days" /></div>
            <div class="col-md-8 form-check form-switch pt-12 ps-14"><input class="form-check-input" type="checkbox" id="is_published" name="is_published" value="1" @checked(old('is_published', $lesson->is_published))><label class="form-check-label" for="is_published">Terbit (tampil ke siswa)</label></div>
        </div>
        <div class="card-footer text-end"><a href="{{ route('skema.show', $module->skema_id) }}" class="btn btn-sm btn-light">Batal</a> <button class="btn btn-sm btn-primary">Simpan</button></div>
    </form>
    <x-slot name="javascript">@include('dashboard.partials.tinymce', ['height' => 400])</x-slot>
</x-dashboard.page>
