<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/** Lonceng notifikasi dashboard (PRD 9.20). */
class NotificationController extends Controller
{
    public function read(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        $url = $notification->data['url'] ?? route('home');

        // hanya tautan internal
        return redirect()->to(str_starts_with($url, url('/')) ? $url : route('home'));
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
