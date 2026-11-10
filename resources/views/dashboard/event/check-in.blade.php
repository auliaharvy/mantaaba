{{-- Check-in (PRD 9.8): pindai QR tiket atau kartu anggota (BarcodeDetector) + input kode manual. --}}
<x-dashboard.page :title="'Check-in: ' . $event->title" :crumbs="['Kegiatan', 'Agenda', 'Check-in']">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <div id="result" class="alert d-none fs-5 fw-bold" role="status" aria-live="assertive"></div>
                    <div id="scanner" class="mb-5 d-none">
                        <video id="video" class="w-100 rounded bg-dark" playsinline muted style="max-height: 360px"></video>
                    </div>
                    <button type="button" id="start-scan" class="btn btn-primary w-100 mb-5"><i class="fa fa-camera"></i> Pindai QR tiket / kartu anggota</button>
                    <form id="manual" class="d-flex gap-2">
                        <label class="visually-hidden" for="code">Kode tiket</label>
                        <input id="code" class="form-control form-control-lg text-uppercase" placeholder="Kode tiket atau nomor MTB-…" autocomplete="off" required>
                        <button class="btn btn-lg btn-success" type="submit">Check-in</button>
                    </form>
                    <p class="text-muted fs-7 mt-4 mb-0">Pemindai kamera membutuhkan Chrome/Edge di Android atau desktop. Bila tidak tersedia, ketik kode tiket atau nomor anggota.</p>
                </div>
            </div>
            <a href="{{ route('event.show', $event) }}" class="btn btn-sm btn-light mt-5"><i class="fa fa-arrow-left"></i> Kembali ke agenda</a>
        </div>
    </div>
    <x-slot name="javascript">
        <script>
            const result = document.getElementById('result');
            let busy = false;
            async function checkIn(code) {
                if (busy || !code) return;
                busy = true;
                try {
                    const res = await fetch('{{ route('event.check-in.store', $event) }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        body: JSON.stringify({ code })
                    });
                    const data = await res.json();
                    result.className = 'alert fs-5 fw-bold ' + (data.ok ? 'alert-success' : 'alert-danger');
                    result.textContent = data.message || 'Gagal';
                } catch (e) {
                    result.className = 'alert alert-danger fs-5 fw-bold';
                    result.textContent = 'Koneksi gagal, coba lagi.';
                }
                setTimeout(() => busy = false, 1500);
            }
            document.getElementById('manual').addEventListener('submit', (e) => {
                e.preventDefault();
                const input = document.getElementById('code');
                checkIn(input.value.trim().toUpperCase());
                input.value = '';
            });
            document.getElementById('start-scan').addEventListener('click', async () => {
                if (!('BarcodeDetector' in window)) {
                    result.className = 'alert alert-warning fs-6';
                    result.textContent = 'Browser ini belum mendukung pemindai kamera. Gunakan input kode manual.';
                    return;
                }
                const detector = new BarcodeDetector({ formats: ['qr_code'] });
                const video = document.getElementById('video');
                video.srcObject = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                await video.play();
                document.getElementById('scanner').classList.remove('d-none');
                (async function scan() {
                    const codes = await detector.detect(video).catch(() => []);
                    if (codes.length) checkIn(codes[0].rawValue.trim());
                    requestAnimationFrame(scan);
                })();
            });
        </script>
    </x-slot>
</x-dashboard.page>
