<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Operations\FarmController;
use App\Http\Controllers\Operations\ItemController;
use App\Http\Controllers\Operations\KitchenController;
use App\Http\Controllers\Operations\PostingRuleController;
use App\Http\Controllers\Operations\StockController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CoaController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\CostCenterController;
use App\Http\Controllers\FiscalPeriodController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\OpeningBalanceController;
use App\Http\Controllers\PartyController;
use App\Http\Controllers\DutyAreaController;
use App\Http\Controllers\DutyController;
use App\Http\Controllers\MyDutyController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\LearnController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\SkemaController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\CoachingNoteController;
use App\Http\Controllers\HealthRecordController;
use App\Http\Controllers\IntegrityPactController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BusinessUnitController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventParticipantController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MyTrainingController;
use App\Http\Controllers\NavMenuController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SliderController;
use App\Http\Controllers\TaxonomyController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
| Dashboard (PRD 6.2): URL /dashboard/{slug}, nama route {modul}.*, DataTables {modul}-datatable.
*/
Route::middleware(['auth', 'active', 'pact'])->prefix('dashboard')->group(function () {
    # Beranda & profil
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('profil', [ProfileController::class, 'index'])->name('profile.index');
    Route::patch('profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('profil/password', [ProfileController::class, 'password'])->name('profile.password');

    /*
    | Website (PRD 9.1)
    */
    Route::get('page-datatable', [PageController::class, 'datatable'])->name('page.datatable');
    Route::get('page/{page}/pratinjau', [PageController::class, 'preview'])->middleware('signed')->name('page.preview');
    Route::resource('page', PageController::class)->except('show');
    Route::post('menu/urutan', [NavMenuController::class, 'reorder'])->name('menu.reorder');
    Route::resource('menu', NavMenuController::class)->except('show');
    Route::get('business-unit-datatable', [BusinessUnitController::class, 'datatable'])->name('business-unit.datatable');
    Route::resource('business-unit', BusinessUnitController::class)->except('show');
    Route::get('slider-datatable', [SliderController::class, 'datatable'])->name('slider.datatable');
    Route::resource('slider', SliderController::class)->except('show');
    Route::get('gallery-datatable', [GalleryController::class, 'datatable'])->name('gallery.datatable');
    Route::post('gallery/{gallery}/photos', [GalleryController::class, 'storePhotos'])->name('gallery.photos.store');
    Route::patch('gallery/{gallery}/photos', [GalleryController::class, 'updatePhotos'])->name('gallery.photos.update');
    Route::delete('gallery-photo/{photo}', [GalleryController::class, 'destroyPhoto'])->name('gallery.photos.destroy');
    Route::resource('gallery', GalleryController::class)->except('show');
    Route::get('document-datatable', [DocumentController::class, 'datatable'])->name('document.datatable');
    Route::resource('document', DocumentController::class)->except('show');
    Route::get('contact-message-datatable', [ContactMessageController::class, 'datatable'])->name('contact-message.datatable');
    Route::resource('contact-message', ContactMessageController::class)->only(['index', 'show', 'destroy']);
    Route::get('setting', [SettingController::class, 'edit'])->name('setting.edit');
    Route::patch('setting', [SettingController::class, 'update'])->name('setting.update');

    # Berita
    Route::get('blog-datatable', [BlogController::class, 'datatable'])->name('blog.datatable');
    Route::resource('blog', BlogController::class);
    Route::get('taksonomi/{type}', [TaxonomyController::class, 'index'])->name('taxonomy.index');
    Route::post('taksonomi/{type}', [TaxonomyController::class, 'store'])->name('taxonomy.store');
    Route::patch('taksonomi/{type}/{id}', [TaxonomyController::class, 'update'])->name('taxonomy.update');
    Route::delete('taksonomi/{type}/{id}', [TaxonomyController::class, 'destroy'])->name('taxonomy.destroy');

    # Notifikasi & pakta integritas
    Route::get('notifikasi/{id}', [NotificationController::class, 'read'])->name('notification.read');
    Route::post('notifikasi/baca-semua', [NotificationController::class, 'readAll'])->name('notification.read-all');
    Route::get('pakta-integritas', [IntegrityPactController::class, 'show'])->name('pact.show');
    Route::post('pakta-integritas', [IntegrityPactController::class, 'sign'])->name('pact.sign');

    /*
    | Penerimaan (PRD 9.2–9.3)
    */
    Route::get('angkatan-datatable', [BatchController::class, 'datatable'])->name('batch.datatable');
    Route::resource('angkatan', BatchController::class)->parameters(['angkatan' => 'batch'])->names('batch');
    Route::get('pendaftar', [ApplicantController::class, 'index'])->name('applicant.index');
    Route::get('pendaftar-datatable', [ApplicantController::class, 'datatable'])->name('applicant.datatable');
    Route::get('pendaftar-export', [ApplicantController::class, 'export'])->name('applicant.export');
    Route::get('pendaftar/{applicant}', [ApplicantController::class, 'show'])->name('applicant.show');
    Route::get('pendaftar/{applicant}/berkas/{key}', [ApplicantController::class, 'document'])->name('applicant.document');
    Route::patch('pendaftar/{applicant}/nilai', [ApplicantController::class, 'scores'])->name('applicant.scores');
    Route::post('pendaftar/{applicant}/tahap', [ApplicantController::class, 'move'])->name('applicant.move');
    Route::post('pendaftar/{applicant}/terima', [ApplicantController::class, 'accept'])->name('applicant.accept');
    Route::post('pendaftar/{applicant}/tolak', [ApplicantController::class, 'reject'])->name('applicant.reject');

    /*
    | Anggota (PRD 9.4, 9.5, 9.19)
    */
    Route::get('anggota-datatable', [MemberController::class, 'datatable'])->name('member.datatable');
    Route::get('anggota-export', [MemberController::class, 'export'])->name('member.export');
    Route::post('anggota-import', [MemberController::class, 'import'])->name('member.import');
    Route::get('anggota/verifikasi/{number}', [MemberController::class, 'verify'])->name('member.verify');
    Route::patch('anggota/{member}/status', [MemberController::class, 'status'])->name('member.status');
    Route::get('anggota/{member}/kartu', [MemberController::class, 'card'])->name('member.card');
    Route::get('anggota/{member}/berkas/{key}', [MemberController::class, 'document'])->name('member.document');
    Route::post('anggota/{member}/berkas', [MemberController::class, 'uploadDocument'])->name('member.documents.store');
    Route::post('anggota/{member}/perjalanan', [MemberController::class, 'storeJourney'])->name('member.journeys.store');
    Route::patch('anggota/{member}/perjalanan/{journey}', [MemberController::class, 'endJourney'])->name('member.journeys.end');
    Route::delete('anggota/{member}/perjalanan/{journey}', [MemberController::class, 'destroyJourney'])->name('member.journeys.destroy');
    Route::resource('anggota', MemberController::class)->parameters(['anggota' => 'member'])->names('member')->except('destroy');
    Route::get('pembinaan', [CoachingNoteController::class, 'index'])->name('coaching.index');
    Route::get('pembinaan-datatable', [CoachingNoteController::class, 'datatable'])->name('coaching.datatable');
    Route::post('pembinaan', [CoachingNoteController::class, 'store'])->name('coaching.store');
    Route::delete('pembinaan/{coaching}', [CoachingNoteController::class, 'destroy'])->name('coaching.destroy');
    Route::get('kesehatan-datatable', [HealthRecordController::class, 'datatable'])->name('health.datatable');
    Route::resource('kesehatan', HealthRecordController::class)->parameters(['kesehatan' => 'health'])->names('health')->except('show');
    Route::get('pengumuman-datatable', [AnnouncementController::class, 'datatable'])->name('announcement.datatable');
    Route::resource('pengumuman', AnnouncementController::class)->parameters(['pengumuman' => 'announcement'])->names('announcement')->except('show');

    /*
    | Pembelajaran, penilaian & sertifikat (PRD 9.6–9.7)
    */
    Route::resource('skema', SkemaController::class);
    Route::post('skema/{skema}/modul', [ModuleController::class, 'store'])->name('module.store');
    Route::post('skema/{skema}/urutan', [ModuleController::class, 'reorder'])->name('skema.reorder');
    Route::patch('modul/{module}', [ModuleController::class, 'update'])->name('module.update');
    Route::delete('modul/{module}', [ModuleController::class, 'destroy'])->name('module.destroy');
    Route::get('modul/{module}/materi/create', [LessonController::class, 'create'])->name('lesson.create');
    Route::post('modul/{module}/materi', [LessonController::class, 'store'])->name('lesson.store');
    Route::get('materi/{lesson}/edit', [LessonController::class, 'edit'])->name('lesson.edit');
    Route::patch('materi/{lesson}', [LessonController::class, 'update'])->name('lesson.update');
    Route::delete('materi/{lesson}', [LessonController::class, 'destroy'])->name('lesson.destroy');
    Route::post('angkatan/{batch}/buka-skema/{skema}', [BatchController::class, 'openSkema'])->name('batch.open-skema');
    Route::get('belajar', [LearnController::class, 'index'])->name('learn.index');
    Route::get('belajar/skema/{skema}', [LearnController::class, 'skema'])->name('learn.skema');
    Route::get('belajar/materi/{lesson}', [LearnController::class, 'lesson'])->name('learn.lesson');
    Route::post('belajar/materi/{lesson}/selesai', [LearnController::class, 'complete'])->name('learn.complete');
    Route::post('belajar/materi/{lesson}/tugas', [LearnController::class, 'submit'])->middleware('throttle:20,1')->name('learn.submit');
    Route::get('belajar/materi/{lesson}/file', [LearnController::class, 'lessonFile'])->name('learn.file');
    Route::get('tugas/{progress}/file', [LearnController::class, 'submissionFile'])->name('submission.file');
    Route::get('tugas-masuk', [SubmissionController::class, 'index'])->name('submission.index');
    Route::get('tugas-masuk-datatable', [SubmissionController::class, 'datatable'])->name('submission.datatable');
    Route::get('tugas-masuk/{progress}', [SubmissionController::class, 'show'])->name('submission.show');
    Route::patch('tugas-masuk/{progress}', [SubmissionController::class, 'review'])->name('submission.review');
    Route::post('tugas-masuk/{progress}/buka-ulang', [SubmissionController::class, 'reopen'])->name('submission.reopen');
    Route::get('penilaian', [AssessmentController::class, 'index'])->name('assessment.index');
    Route::post('penilaian', [AssessmentController::class, 'store'])->name('assessment.store');
    Route::post('penilaian/terbitkan', [AssessmentController::class, 'issue'])->name('assessment.issue');
    Route::get('sertifikat', [CertificateController::class, 'index'])->name('certificate.index');
    Route::get('sertifikat-datatable', [CertificateController::class, 'datatable'])->name('certificate.datatable');
    Route::post('anggota/{member}/kukuhkan', [CertificateController::class, 'graduate'])->name('certificate.graduate');

    /*
    | Akuntansi (PRD 9.13)
    */
    Route::resource('coa', CoaController::class)->except('show');
    Route::get('pihak-datatable', [PartyController::class, 'datatable'])->name('party.datatable');
    Route::get('pihak-cari', [PartyController::class, 'search'])->name('party.search');
    Route::resource('pihak', PartyController::class)->parameters(['pihak' => 'party'])->names('party')->except(['show', 'destroy']);
    Route::get('pusat-biaya', [CostCenterController::class, 'index'])->name('cost-center.index');
    Route::post('pusat-biaya', [CostCenterController::class, 'store'])->name('cost-center.store');
    Route::patch('pusat-biaya/{costCenter}', [CostCenterController::class, 'update'])->name('cost-center.update');
    Route::get('periode', [FiscalPeriodController::class, 'index'])->name('period.index');
    Route::get('periode/{period}', [FiscalPeriodController::class, 'show'])->name('period.show');
    Route::post('periode/{period}/rekonsiliasi', [FiscalPeriodController::class, 'reconcile'])->name('period.reconcile');
    Route::post('periode/{period}/jurnal-otomatis', [FiscalPeriodController::class, 'generate'])->name('period.generate');
    Route::post('periode/{period}/tutup', [FiscalPeriodController::class, 'close'])->name('period.close');
    Route::post('tutup-buku', [FiscalPeriodController::class, 'closeYear'])->name('period.close-year');
    Route::get('laporan-keuangan', [ReportController::class, 'index'])->name('report.index');
    Route::post('laporan-keuangan/transparansi/terbitkan', [ReportController::class, 'publishTransparency'])->name('report.publish-transparency');
    Route::get('laporan-keuangan/{report}', [ReportController::class, 'show'])->name('report.show');
    Route::get('anggaran', [BudgetController::class, 'index'])->name('budget.index');
    Route::post('anggaran', [BudgetController::class, 'store'])->name('budget.store');
    Route::delete('anggaran/{budget}', [BudgetController::class, 'destroy'])->name('budget.destroy');
    Route::get('donasi-datatable', [DonationController::class, 'datatable'])->name('donation.datatable');
    Route::post('donasi/{donation}/verifikasi', [DonationController::class, 'verify'])->name('donation.verify');
    Route::post('donasi/{donation}/tolak', [DonationController::class, 'reject'])->name('donation.reject');
    Route::get('donasi/{donation}/bukti', [DonationController::class, 'proof'])->name('donation.proof');
    Route::resource('donasi', DonationController::class)->parameters(['donasi' => 'donation'])->names('donation')->only(['index', 'create', 'store', 'show']);
    Route::post('periode/{period}/buka', [FiscalPeriodController::class, 'reopen'])->name('period.reopen');
    Route::get('saldo-awal', [OpeningBalanceController::class, 'show'])->name('opening.show');
    Route::post('saldo-awal', [OpeningBalanceController::class, 'store'])->name('opening.store');
    Route::get('voucher-datatable', [JournalController::class, 'datatable'])->name('journal.datatable');
    Route::get('voucher-antrean', [JournalController::class, 'queue'])->name('journal.queue');
    Route::post('voucher-posting', [JournalController::class, 'postMany'])->name('journal.post-many');
    Route::post('voucher/{journal}/ajukan', [JournalController::class, 'submit'])->name('journal.submit');
    Route::post('voucher/{journal}/posting', [JournalController::class, 'post'])->name('journal.post');
    Route::post('voucher/{journal}/tolak', [JournalController::class, 'reject'])->name('journal.reject');
    Route::post('voucher/{journal}/balik', [JournalController::class, 'reverse'])->name('journal.reverse');
    Route::get('voucher/{journal}/lampiran/{index}', [JournalController::class, 'attachment'])->name('journal.attachment');
    Route::get('voucher/{journal}/pdf', [JournalController::class, 'pdf'])->name('journal.pdf');
    Route::post('kesehatan/{health}/voucher', [JournalController::class, 'fromHealth'])->name('journal.from-health');
    Route::resource('voucher', JournalController::class)->parameters(['voucher' => 'journal'])->names('journal');
    Route::get('buku-besar', [LedgerController::class, 'ledger'])->name('ledger.index');
    Route::get('neraca-saldo', [LedgerController::class, 'trialBalance'])->name('ledger.trial-balance');
    Route::get('buku-kas', [LedgerController::class, 'cashBook'])->name('ledger.cash-book');

    /*
    | Asrama & piket (PRD 9.9)
    */
    Route::resource('kamar', RoomController::class)->parameters(['kamar' => 'room'])->names('room')->except('show');
    Route::post('kamar/{room}/penghuni', [RoomController::class, 'assign'])->name('room.assign');
    Route::delete('penghuni/{member}', [RoomController::class, 'unassign'])->name('room.unassign');
    Route::get('piket', [DutyController::class, 'index'])->name('duty.index');
    Route::post('piket/buat', [DutyController::class, 'generate'])->name('duty.generate');
    Route::post('piket/tukar', [DutyController::class, 'swap'])->name('duty.swap');
    Route::get('piket/laporan', [DutyController::class, 'report'])->name('duty.report');
    Route::patch('piket/{duty}', [DutyController::class, 'update'])->name('duty.update');
    Route::delete('piket/{duty}', [DutyController::class, 'destroy'])->name('duty.destroy');
    Route::post('piket/{duty}/verifikasi', [DutyController::class, 'verify'])->name('duty.verify');
    Route::get('piket/{duty}/foto', [DutyController::class, 'photo'])->name('duty.photo');
    Route::resource('area-piket', DutyAreaController::class)->parameters(['area-piket' => 'duty_area'])->names('duty-area')->only(['index', 'store', 'update', 'destroy']);
    Route::get('piket-saya', [MyDutyController::class, 'index'])->name('my-duty.index');
    Route::post('piket-saya/{duty}/selesai', [MyDutyController::class, 'complete'])->name('my-duty.complete');

    /*
    | Agenda (PRD 9.8; disederhanakan di M4)
    */
    Route::get('event-datatable', [EventController::class, 'datatable'])->name('event.datatable');
    Route::get('event-kalender', [EventController::class, 'calendar'])->name('event.calendar');
    Route::get('sedulur-selawase', [EventController::class, 'selawase'])->name('event.selawase');
    Route::post('event/{event}/sinkron-peserta', [EventParticipantController::class, 'sync'])->name('event.participants.sync');
    Route::post('event/{event}/salin', [EventController::class, 'duplicate'])->name('event.duplicate');
    Route::resource('event', EventController::class);
    Route::get('event/{event}/peserta-datatable', [EventParticipantController::class, 'datatable'])->name('event.participants.datatable');
    Route::get('event/{event}/peserta-kandidat', [EventParticipantController::class, 'candidates'])->name('event.participants.candidates');
    Route::post('event/{event}/peserta', [EventParticipantController::class, 'store'])->name('event.participants.store');
    Route::patch('event/{event}/kehadiran', [EventParticipantController::class, 'attendance'])->name('event.attendance');
    Route::get('event/{event}/check-in', [EventParticipantController::class, 'checkInPage'])->name('event.check-in');
    Route::post('event/{event}/check-in', [EventParticipantController::class, 'checkIn'])->name('event.check-in.store');
    Route::get('event/{event}/peserta-export', [EventParticipantController::class, 'export'])->name('event.participants.export');
    Route::post('event/{event}/pengumuman', [EventParticipantController::class, 'announce'])->name('event.announce');
    Route::patch('peserta/{participant}/status', [EventParticipantController::class, 'status'])->name('participant.status');
    Route::get('sertifikat/{certificate}/unduh', [CertificateController::class, 'download'])->name('certificate.download');
    Route::patch('sertifikat/{certificate}/cabut', [CertificateController::class, 'revoke'])->name('certificate.revoke');
    Route::post('sertifikat/{certificate}/terbit-ulang', [CertificateController::class, 'reissue'])->name('certificate.reissue');
    Route::get('agenda-saya', [MyTrainingController::class, 'index'])->name('my-training.index');
    Route::get('agenda-saya/{participant}', [MyTrainingController::class, 'show'])->name('my-training.show');

    /*
    | Sistem (PRD 9.18, 9.20)
    */
    Route::get('pengguna-datatable', [UserController::class, 'datatable'])->name('user.datatable');
    Route::get('pengguna/{user}/hak-akses', [UserController::class, 'permissions'])->name('user.permissions');
    Route::put('pengguna/{user}/hak-akses', [UserController::class, 'updatePermissions'])->name('user.permissions.update');
    // M6: stok, kebun & kolam, dapur (PRD 9.10–9.12)
    Route::resource('barang', ItemController::class)->parameters(['barang' => 'item'])->names('item')->except(['destroy']);
    Route::get('stok/mutasi', [StockController::class, 'moveForm'])->name('stock.move-form');
    Route::post('stok/mutasi', [StockController::class, 'move'])->name('stock.move');
    Route::get('stok/pembelian', [StockController::class, 'purchases'])->name('stock.purchases');
    Route::get('stok/pembelian/baru', [StockController::class, 'purchaseForm'])->name('stock.purchase-form');
    Route::post('stok/pembelian', [StockController::class, 'purchase'])->name('stock.purchase');
    Route::post('stok/pembelian/{journal}/batal', [StockController::class, 'cancelPurchase'])->name('stock.purchase-cancel');
    Route::get('stok/opname', [StockController::class, 'opnames'])->name('stock.opnames');
    Route::post('stok/opname', [StockController::class, 'startOpname'])->name('stock.opname-start');
    Route::get('stok/opname/{opname}', [StockController::class, 'opname'])->name('stock.opname');
    Route::post('stok/opname/{opname}', [StockController::class, 'saveOpname'])->name('stock.opname-save');
    Route::get('kebun', [FarmController::class, 'index'])->name('farm.index');
    Route::get('kebun/petak/baru', [FarmController::class, 'createPlot'])->name('farm.plot-create');
    Route::post('kebun/petak', [FarmController::class, 'storePlot'])->name('farm.plot-store');
    Route::get('kebun/petak/{plot}/edit', [FarmController::class, 'editPlot'])->name('farm.plot-edit');
    Route::put('kebun/petak/{plot}', [FarmController::class, 'updatePlot'])->name('farm.plot-update');
    Route::get('kebun/petak/{plot}/siklus-baru', [FarmController::class, 'createCycle'])->name('farm.cycle-create');
    Route::post('kebun/petak/{plot}/siklus', [FarmController::class, 'storeCycle'])->name('farm.cycle-store');
    Route::get('kebun/siklus/{cycle}', [FarmController::class, 'cycle'])->name('farm.cycle');
    Route::post('kebun/siklus/{cycle}/log', [FarmController::class, 'log'])->name('farm.log');
    Route::post('kebun/siklus/{cycle}/panen', [FarmController::class, 'harvest'])->name('farm.harvest');
    Route::post('kebun/siklus/{cycle}/tutup', [FarmController::class, 'close'])->name('farm.close');
    Route::post('kebun/siklus/{cycle}/buka', [FarmController::class, 'reopen'])->name('farm.reopen');
    Route::get('dapur', [KitchenController::class, 'index'])->name('kitchen.index');
    Route::get('dapur/menu', [KitchenController::class, 'menus'])->name('kitchen.menus');
    Route::get('dapur/menu/baru', [KitchenController::class, 'createMenu'])->name('kitchen.menu-create');
    Route::post('dapur/menu', [KitchenController::class, 'storeMenu'])->name('kitchen.menu-store');
    Route::get('dapur/menu/{menu}/edit', [KitchenController::class, 'editMenu'])->name('kitchen.menu-edit');
    Route::put('dapur/menu/{menu}', [KitchenController::class, 'updateMenu'])->name('kitchen.menu-update');
    Route::get('dapur/rencana', [KitchenController::class, 'plans'])->name('kitchen.plans');
    Route::post('dapur/rencana', [KitchenController::class, 'savePlans'])->name('kitchen.plans-save');
    Route::get('dapur/kebutuhan', [KitchenController::class, 'needs'])->name('kitchen.needs');
    Route::get('dapur/masak/{plan}', [KitchenController::class, 'cookForm'])->name('kitchen.cook-form');
    Route::post('dapur/masak/{plan}', [KitchenController::class, 'cook'])->name('kitchen.cook');
    Route::get('dapur/laporan', [KitchenController::class, 'report'])->name('kitchen.report');
    Route::get('setting-akuntansi', [PostingRuleController::class, 'edit'])->name('posting-rule.edit');
    Route::put('setting-akuntansi', [PostingRuleController::class, 'update'])->name('posting-rule.update');

    Route::post('pengguna/{user}/tautan-password', [UserController::class, 'passwordLink'])->name('user.password-link');
    Route::resource('pengguna', UserController::class)->parameters(['pengguna' => 'user'])->names('user')->except(['show', 'destroy']);
    Route::get('log-aktivitas', [ActivityLogController::class, 'index'])->name('activity-log.index');
    Route::get('log-aktivitas-datatable', [ActivityLogController::class, 'datatable'])->name('activity-log.datatable');
});
