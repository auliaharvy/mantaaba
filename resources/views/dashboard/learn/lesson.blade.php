<x-dashboard.page :title="$lesson->title" :crumbs="['Belajar', $skema->code, $lesson->module->title]">
    <div class="card mb-5">
        <div class="card-header flex-wrap py-3">
            <div class="card-title flex-column align-items-start">
                <span class="text-muted fs-7">{{ $skema->code }} › {{ $lesson->module->title }} › Materi {{ $position }}/{{ $total }}</span>
                <span class="fs-3 fw-bolder">{{ $lesson->title }}</span>
            </div>
            <div class="card-toolbar"><a href="{{ route('learn.skema', $skema) }}" class="btn btn-sm btn-light-primary"><i class="fa fa-list"></i> Daftar materi</a></div>
        </div>
        <div class="card-body">
            @if ($lesson->type === 'video' && $lesson->embedUrl())
                <div class="ratio ratio-16x9 mb-6"><iframe src="{{ $lesson->embedUrl() }}" title="{{ $lesson->title }}" allowfullscreen loading="lazy"></iframe></div>
            @endif
            @if ($lesson->body)<div class="fs-6 mb-6">{!! $lesson->body !!}</div>@endif
            @if ($lesson->file)<a href="{{ route('learn.file', $lesson) }}" class="btn btn-sm btn-light"><i class="fa fa-download"></i> Unduh materi</a>@endif
        </div>
        @unless ($lesson->isAssignment())
            <div class="card-footer d-flex justify-content-between align-items-center">
                <span class="text-muted fs-7">{{ $progress?->completed_at ? 'Selesai ' . $progress->completed_at->translatedFormat('j M Y') : '' }}</span>
                @unless ($progress?->completed_at)
                    <form method="POST" action="{{ route('learn.complete', $lesson) }}">@csrf<button class="btn btn-sm btn-success"><i class="fa fa-check"></i> Tandai selesai</button></form>
                @endunless
            </div>
        @endunless
    </div>

    @if ($lesson->isAssignment())
        <div class="card mb-5">
            <div class="card-header"><div class="card-title fs-4 fw-bolder">Tugas{{ $due ? ' · tenggat ' . $due->translatedFormat('j M Y') : '' }}</div>
                @if ($due && $due->isPast() && ! $progress?->submitted_at)<div class="card-toolbar"><span class="badge badge-light-danger">Lewat tenggat</span></div>@endif</div>
            <div class="card-body">
                @if ($progress?->submitted_at)
                    <div class="mb-5">
                        <div class="text-muted fs-7">Dikirim {{ $progress->submitted_at->translatedFormat('j M Y H:i') }}</div>
                        @if ($progress->submission_text)<div class="border rounded p-4 bg-light mt-2" style="white-space: pre-line">{{ $progress->submission_text }}</div>@endif
                        @if ($progress->submission_file)<a href="{{ route('submission.file', $progress) }}" class="btn btn-sm btn-light mt-2"><i class="fa fa-paperclip"></i> File saya</a>@endif
                    </div>
                @endif
                @if ($progress?->reviewed_at)
                    <div class="alert alert-light-success"><div class="fw-bolder fs-5">Nilai {{ $progress->score }}</div><div style="white-space: pre-line">{{ $progress->feedback }}</div>
                        <div class="fs-8 text-muted mt-2">{{ $progress->reviewer?->name }} · {{ $progress->reviewed_at->translatedFormat('j M Y') }}</div></div>
                @else
                    @if ($progress?->feedback)<div class="alert alert-light-warning"><div class="fw-bold">Catatan mentor (tugas dibuka ulang)</div><div style="white-space: pre-line">{{ $progress->feedback }}</div></div>@endif
                    <form method="POST" action="{{ route('learn.submit', $lesson) }}" enctype="multipart/form-data">
                        @csrf
                        <x-dashboard.field name="submission_text" label="Jawaban (teks)" type="textarea" rows="5" :value="$progress?->submission_text" />
                        <x-dashboard.field name="submission_file" label="File (pdf, docx, xlsx, gambar, zip · maks. 10 MB)" type="file" />
                        <button class="btn btn-sm btn-primary">{{ $progress?->submitted_at ? 'Kirim ulang' : 'Kirim tugas' }}</button>
                    </form>
                @endif
            </div>
        </div>
    @endif

    <div class="d-flex justify-content-between">
        @if ($previous)<a href="{{ route('learn.lesson', $previous) }}" class="btn btn-sm btn-light"><i class="fa fa-arrow-left"></i> {{ Str::limit($previous->title, 30) }}</a>@else<span></span>@endif
        @if ($next)<a href="{{ route('learn.lesson', $next) }}" class="btn btn-sm btn-light">{{ Str::limit($next->title, 30) }} <i class="fa fa-arrow-right"></i></a>@endif
    </div>
</x-dashboard.page>
