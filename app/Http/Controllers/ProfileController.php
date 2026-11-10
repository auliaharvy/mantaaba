<?php

namespace App\Http\Controllers;

use App\Support\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/** Profil pengguna yang sedang login (PRD 9.4). Tidak pernah menerima id pengguna dari URL. */
class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $participations = $user->participants()->with('event', 'certificateRecord')->latest()->get();
        // PRD 9.4: anggota melihat data sendiri, catatan pembinaan ber-visibilitas anggota, dan riwayat kesehatan sendiri
        $member = $user->member?->load('batch', 'journeys.businessUnit');
        $notes = $member ? $member->coachingNotes()->where('visibility', 'anggota')->with('author')->get() : collect();
        $health = $member ? $member->healthRecords()->get() : collect();

        return view('dashboard.profile.index', compact('user', 'participations', 'member', 'notes', 'health'));
    }

    public function update(Request $request)
    {
        $user = $request->user();
        if ($member = $user->member) {
            // anggota hanya mengubah foto, telepon, dan alamat; data lain dikelola pengurus
            $data = $request->validate(['phone_number' => 'nullable|string|max:30', 'address' => 'nullable|string|max:255', 'photo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048']);
            if ($request->hasFile('photo')) {
                $member->photo = Upload::store($request->file('photo'), 'members', $member->photo, 800);
            }
            $member->fill(['phone' => $data['phone_number'] ?? null, 'address' => $data['address'] ?? null])->save();
            $user->update(['phone_number' => $data['phone_number'] ?? null, 'address' => $data['address'] ?? null]);

            return redirect()->route('profile.index')->with('success', 'Profil diperbarui.');
        }
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'gender' => 'nullable|in:L,P',
            'birthplace' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date|before:today',
            'address' => 'nullable|string|max:500',
            'phone_number' => 'nullable|string|max:30',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'photo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        unset($data['photo']);
        if ($request->hasFile('photo')) {
            // disimpan sebagai nama file di folder user/ (pola lama dipertahankan agar foto lama tetap tampil)
            $data['photo'] = basename(Upload::store($request->file('photo'), 'user', $user->photo ? 'user/' . $user->photo : null, 600));
        }
        $user->update($data);

        return redirect()->route('profile.index')->with('success', 'Profil diperbarui.');
    }

    public function password(Request $request)
    {
        $request->validate(['password' => ['required', 'confirmed', Password::min(8)]]);
        $request->user()->update(['password' => Hash::make($request->input('password'))]);

        return redirect()->route('profile.index')->with('success', 'Password berhasil diubah.');
    }
}
