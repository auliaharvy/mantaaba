<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AdministratorController;
use App\Http\Controllers\BoardMemberController;
use App\Http\Controllers\BoardPeriodController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\MemberDueController;
use App\Http\Controllers\MemberImportController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\GeneralController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\EventParticipantController;
use App\Http\Controllers\MyTrainingController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResearchController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SliderController;
use App\Http\Controllers\SuspensionController;
use App\Http\Controllers\TaxonomyController;
use App\Http\Controllers\ThreadController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('dashboard')->group(function () {
    # Beranda
    Route::get('/', [HomeController::class, 'index'])->name('home');

    # Profil
    Route::get('profil', [ProfileController::class, 'index'])->name('profile.index');
    Route::patch('profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('profil/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::patch('profil/notifikasi', [ProfileController::class, 'notifications'])->name('profile.notifications');

    /*
    |--------------------------------------------------------
    | Postingan
    |--------------------------------------------------------
    */
    # Blog
    Route::resource('blog', BlogController::class);
    Route::get('blog-datatable', [BlogController::class, 'datatable'])->name('blog.datatable');

    # Forum
    Route::resource('thread', ThreadController::class);
    Route::get('thread-datatable', [ThreadController::class, 'datatable'])->name('thread.datatable');
    Route::patch('thread/{thread}/moderasi', [ThreadController::class, 'moderate'])->name('thread.moderate');
    Route::get('report-datatable', [ReportController::class, 'datatable'])->name('report.datatable');
    Route::get('laporan-forum', [ReportController::class, 'index'])->name('report.index');
    Route::patch('laporan-forum/{report}', [ReportController::class, 'resolve'])->name('report.resolve');
    Route::get('taksonomi/{type}', [TaxonomyController::class, 'index'])->name('taxonomy.index');
    Route::post('taksonomi/{type}', [TaxonomyController::class, 'store'])->name('taxonomy.store');
    Route::patch('taksonomi/{type}/{id}', [TaxonomyController::class, 'update'])->name('taxonomy.update');
    Route::delete('taksonomi/{type}/{id}', [TaxonomyController::class, 'destroy'])->name('taxonomy.destroy');

    # Komentar
    Route::resource('comment', CommentController::class)->only(['edit', 'update', 'destroy']);

    # Jurnal
    Route::resource('research', ResearchController::class);
    Route::get('research-datatable', [ResearchController::class, 'datatable'])->name('research.datatable');

    /*
    |--------------------------------------------------------
    | Kegiatan
    |--------------------------------------------------------
    */
    # Acara
    # Acara (PRD 8.9)
    Route::get('event-datatable', [EventController::class, 'datatable'])->name('event.datatable');
    Route::post('event/{event}/salin', [EventController::class, 'duplicate'])->name('event.duplicate');
    Route::post('event/{event}/sertifikat', [EventController::class, 'issueCertificates'])->name('event.certificates');
    Route::resource('event', EventController::class);

    # Peserta per event
    Route::get('event/{event}/peserta-datatable', [EventParticipantController::class, 'datatable'])->name('event.participants.datatable');
    Route::get('event/{event}/peserta-kandidat', [EventParticipantController::class, 'candidates'])->name('event.participants.candidates');
    Route::post('event/{event}/peserta', [EventParticipantController::class, 'store'])->name('event.participants.store');
    Route::patch('event/{event}/kehadiran', [EventParticipantController::class, 'attendance'])->name('event.attendance');
    Route::get('event/{event}/check-in', [EventParticipantController::class, 'checkInPage'])->name('event.check-in');
    Route::post('event/{event}/check-in', [EventParticipantController::class, 'checkIn'])->name('event.check-in.store');
    Route::get('event/{event}/peserta-export', [EventParticipantController::class, 'export'])->name('event.participants.export');
    Route::post('event/{event}/pengumuman', [EventParticipantController::class, 'announce'])->name('event.announce');
    Route::patch('peserta/{participant}/status', [EventParticipantController::class, 'status'])->name('participant.status');
    Route::get('peserta/{participant}/bukti', [EventParticipantController::class, 'proof'])->name('participant.proof');
    Route::patch('peserta/{participant}/sertifikat-manual', [EventParticipantController::class, 'manualCertificate'])->name('participant.manual-certificate');

    # Sertifikat
    Route::get('sertifikat/{certificate}/unduh', [CertificateController::class, 'download'])->name('certificate.download');
    Route::patch('sertifikat/{certificate}/cabut', [CertificateController::class, 'revoke'])->name('certificate.revoke');
    Route::post('sertifikat/{certificate}/terbit-ulang', [CertificateController::class, 'reissue'])->name('certificate.reissue');

    # Pelatihan saya (peserta)
    Route::get('pelatihan-saya', [MyTrainingController::class, 'index'])->name('my-training.index');
    Route::get('pelatihan-saya/{participant}', [MyTrainingController::class, 'show'])->name('my-training.show');
    Route::post('pelatihan-saya/{participant}/bukti', [MyTrainingController::class, 'uploadProof'])->name('my-training.proof');

    /*
    |--------------------------------------------------------
    | Pengguna
    |--------------------------------------------------------
    */
    # Anggota
    Route::resource('member', MemberController::class);
    Route::get('member-datatable', [MemberController::class, 'datatable'])->name('member.datatable');
    Route::get('member/general/create', [MemberController::class, 'general'])->name('member.general');
    Route::patch('member/general/assign', [MemberController::class, 'generalAssign'])->name('member.general-assign');
    Route::get('member-select2-user', [MemberController::class, 'select2_user'])->name('member.select2-user');
    Route::get('get/general/{id}', [MemberController::class, 'getGeneral'])->name('member.get-general');

    # Divisi
    Route::resource('division', DivisionController::class);
    Route::get('division-datatable', [DivisionController::class, 'datatable'])->name('division.datatable');
    Route::get('division/{division}/position', [DivisionController::class, 'position'])->name('division.position');

    # Posisi
    Route::resource('position', PositionController::class);
    Route::get('position-datatable', [PositionController::class, 'datatable'])->name('position.datatable');
    Route::get('position-select2-division', [PositionController::class, 'select2_division'])->name('position.select2-division');

    # Umum
    Route::resource('general', GeneralController::class);
    Route::get('general-datatable', [GeneralController::class, 'datatable'])->name('general.datatable');

    /*
    |--------------------------------------------------------
    | Pengaturan
    |--------------------------------------------------------
    */
    # Administrator
    Route::resource('administrator', AdministratorController::class);
    Route::get('administrator-datatable', [AdministratorController::class, 'datatable'])->name('administrator.datatable');

    # Aplikasi
    Route::resource('setting', SettingController::class)->only(['edit', 'update']);

    /*
    |--------------------------------------------------------
    | Website / CMS (M2)
    |--------------------------------------------------------
    */
    Route::get('page-datatable', [PageController::class, 'datatable'])->name('page.datatable');
    Route::resource('page', PageController::class)->only(['index', 'edit', 'update']);
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

    # Log aktivitas (PRD 8.13)
    Route::get('log-aktivitas', [ActivityLogController::class, 'index'])->name('activity-log.index');
    Route::get('log-aktivitas-datatable', [ActivityLogController::class, 'datatable'])->name('activity-log.datatable');

    /*
    |--------------------------------------------------------
    | Organisasi & Keanggotaan (M1)
    |--------------------------------------------------------
    */
    Route::get('organization-datatable', [OrganizationController::class, 'datatable'])->name('organization.datatable');
    Route::resource('organization', OrganizationController::class);

    # Data anggota (PRD 8.2-8.3)
    Route::get('keanggotaan-datatable', [MembershipController::class, 'datatable'])->name('membership.datatable');
    Route::get('keanggotaan-export', [MembershipController::class, 'export'])->name('membership.export');
    Route::get('keanggotaan-ppl-export', [MembershipController::class, 'pplExport'])->name('membership.ppl-export');
    Route::get('keanggotaan-import', [MemberImportController::class, 'create'])->name('membership.import.form');
    Route::get('keanggotaan-import/template', [MemberImportController::class, 'template'])->name('membership.import.template');
    Route::post('keanggotaan-import', [MemberImportController::class, 'store'])->name('membership.import.store');
    Route::get('keanggotaan-import/gagal/{report}', [MemberImportController::class, 'failures'])->name('membership.import.failures');
    Route::get('keanggotaan-usulan-skors', [SuspensionController::class, 'index'])->name('membership.suspensions');
    Route::get('keanggotaan-usulan-skors-datatable', [SuspensionController::class, 'datatable'])->name('membership.suspensions.datatable');
    Route::patch('keanggotaan/{membership}/skors', [SuspensionController::class, 'update'])->name('membership.suspend');
    Route::post('keanggotaan/{membership}/aktivasi', [MembershipController::class, 'sendActivation'])->name('membership.activation');
    Route::post('keanggotaan/{membership}/iuran', [MemberDueController::class, 'store'])->name('membership.dues.store');
    Route::delete('iuran/{due}', [MemberDueController::class, 'destroy'])->name('dues.destroy');
    Route::get('iuran/{due}/bukti', [MemberDueController::class, 'proof'])->name('dues.proof');
    Route::resource('keanggotaan', MembershipController::class)->parameters(['keanggotaan' => 'membership'])->names('membership');

    # Kepengurusan (PRD 8.4)
    Route::get('kepengurusan-datatable', [BoardPeriodController::class, 'datatable'])->name('board.datatable');
    Route::get('kepengurusan-kandidat', [BoardPeriodController::class, 'candidates'])->name('board.candidates');
    Route::post('kepengurusan/{board}/salin', [BoardPeriodController::class, 'duplicate'])->name('board.duplicate');
    Route::post('kepengurusan/{board}/pengurus', [BoardMemberController::class, 'store'])->name('board-member.store');
    Route::delete('pengurus/{boardMember}', [BoardMemberController::class, 'destroy'])->name('board-member.destroy');
    Route::resource('kepengurusan', BoardPeriodController::class)->parameters(['kepengurusan' => 'board'])->names('board');
});
