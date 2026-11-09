{{-- Tombol laporkan (PRD 8.7). $type: thread|comment --}}
@auth
    <details class="text-sm">
        <summary class="cursor-pointer list-none text-gray-500 hover:text-red-700">Laporkan</summary>
        <form method="POST" action="{{ route('site.forum.report') }}" class="mt-2 flex flex-col gap-2 sm:flex-row">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}"><input type="hidden" name="id" value="{{ $id }}">
            <label class="sr-only" for="reason-{{ $type }}-{{ $id }}">Alasan laporan</label>
            <input id="reason-{{ $type }}-{{ $id }}" name="reason" required minlength="5" maxlength="500" placeholder="Alasan (mis. spam, tidak pantas)" class="flex-1 rounded-md border-brand-border text-sm">
            <button class="rounded-md bg-red-700 px-3 py-1.5 text-sm font-semibold text-white">Kirim</button>
        </form>
    </details>
@endauth
