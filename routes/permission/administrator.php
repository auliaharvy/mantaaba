<?php

use App\Http\Controllers\ActivityLogController;
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
    | Agenda (PRD 9.8; disederhanakan di M4)
    */
    Route::get('event-datatable', [EventController::class, 'datatable'])->name('event.datatable');
    Route::post('event/{event}/salin', [EventController::class, 'duplicate'])->name('event.duplicate');
    Route::post('event/{event}/sertifikat', [EventController::class, 'issueCertificates'])->name('event.certificates');
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
    Route::get('peserta/{participant}/bukti', [EventParticipantController::class, 'proof'])->name('participant.proof');
    Route::patch('peserta/{participant}/sertifikat-manual', [EventParticipantController::class, 'manualCertificate'])->name('participant.manual-certificate');
    Route::get('peserta/{participant}/sertifikat-manual', [EventParticipantController::class, 'manualCertificateDownload'])->name('participant.manual-certificate.download');
    Route::get('sertifikat/{certificate}/unduh', [CertificateController::class, 'download'])->name('certificate.download');
    Route::patch('sertifikat/{certificate}/cabut', [CertificateController::class, 'revoke'])->name('certificate.revoke');
    Route::post('sertifikat/{certificate}/terbit-ulang', [CertificateController::class, 'reissue'])->name('certificate.reissue');
    Route::get('agenda-saya', [MyTrainingController::class, 'index'])->name('my-training.index');
    Route::get('agenda-saya/{participant}', [MyTrainingController::class, 'show'])->name('my-training.show');
    Route::post('agenda-saya/{participant}/bukti', [MyTrainingController::class, 'uploadProof'])->name('my-training.proof');

    /*
    | Sistem (PRD 9.18, 9.20)
    */
    Route::get('pengguna-datatable', [UserController::class, 'datatable'])->name('user.datatable');
    Route::get('pengguna/{user}/hak-akses', [UserController::class, 'permissions'])->name('user.permissions');
    Route::put('pengguna/{user}/hak-akses', [UserController::class, 'updatePermissions'])->name('user.permissions.update');
    Route::post('pengguna/{user}/tautan-password', [UserController::class, 'passwordLink'])->name('user.password-link');
    Route::resource('pengguna', UserController::class)->parameters(['pengguna' => 'user'])->names('user')->except(['show', 'destroy']);
    Route::get('log-aktivitas', [ActivityLogController::class, 'index'])->name('activity-log.index');
    Route::get('log-aktivitas-datatable', [ActivityLogController::class, 'datatable'])->name('activity-log.datatable');
});
