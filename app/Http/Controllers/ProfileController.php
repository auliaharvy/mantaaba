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

        return view('dashboard.profile.index', compact('user', 'participations'));
    }

    public function update(Request $request)
    {
        $user = $request->user();
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
