<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Comment;
use App\Models\Participant;
use App\Models\Thread;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:profile-view'])->only(['index', 'datatable']);
        $this->middleware(['permission:profile-update'])->only(['update', 'password']);
    }

    public function index()
    {
        $user = Auth::user();
        $member = $user->member()->with('organization', 'dues')->first();

        // Counter //
        $counter = [
            'blog' => Blog::select('id')->where('user_id', '=', $user->id)->count(),
            'thread' => Thread::select('id')->where('user_id', '=', $user->id)->count(),
            'comment' =>  Comment::select('id')->where('user_id', '=', $user->id)->count(),
            'participant' => Participant::select('id')->where('user_id', '=', $user->id)->count()
        ];

        // Tanggal Lahir //
        $date_of_birth = Carbon::parse($user->date_of_birth)->locale('id');
        $date_of_birth->settings(['formatFunction' => 'translatedFormat']);
        $date_of_birth->format('j F Y');

        // 8.11: Pelatihan (event diikuti, sertifikat, jam PPL)
        $participations = $user->participants()->with('event', 'certificateRecord')->latest()->get();
        $pplHours = $user->pplHoursByYear();

        // 8.11: Tulisan saya
        $writings = [
            'blogs' => $user->blogs()->latest()->take(20)->get(),
            'threads' => $user->threads()->latest()->take(20)->get(),
        ];

        return view('dashboard.profile.index', compact('user', 'counter', 'date_of_birth', 'member', 'writings', 'participations', 'pplHours'));
    }

    /**
     * Update User Profile.
     */
    public function update(Request $request)
    {
        // B1: hanya user yang sedang login, tidak pernah dari URL
        $user = $request->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'gender' => 'required|in:L,P',
            'birthplace' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'address' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id
        ]);

        if ($request->hasFile('photo')) {
            $request->validate([
                'photo' => 'required|file|max:2048|mimes:jpeg,jpg,png,webp'
            ]);

            if (Storage::exists('public/user/' . $user->photo)) {
                Storage::delete('public/user/' . $user->photo);
            }

            $photoFile = $request->file('photo');
            $photoName = $photoFile->hashName();
            Storage::putFileAs('public/user', $photoFile, $photoName);

            $user->photo = $photoName;
        }

        $user->name = $request->input('name');
        $user->gender = $request->input('gender');
        $user->birthplace = $request->input('birthplace');
        $user->date_of_birth = $request->input('date_of_birth');
        $user->address = $request->input('address');
        $user->phone_number = $request->input('phone_number');
        $user->email = $request->input('email');
        $user->save();

        return redirect()->route('profile.index')->with('success', 'Informasi akun telah diubah.');
    }

    /** Preferensi notifikasi (PRD 8.7, 8.11). */
    public function notifications(Request $request)
    {
        $request->user()->update(['notify_forum_replies' => $request->boolean('notify_forum_replies')]);

        return redirect()->route('profile.index')->with('success', 'Preferensi notifikasi disimpan.');
    }

    /**
     * Update User Password.
     */
    public function password(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
            'password_confirm' => ['same:password'],
        ]);

        if ($request->filled('password')) {
            $user->password = Hash::make($request->input('password'));
        }

        $user->save();

        return redirect()->route('profile.index')->with('success', 'Password berhasil diubah.');
    }

}
