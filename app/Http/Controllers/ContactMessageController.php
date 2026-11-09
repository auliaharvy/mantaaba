<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

/** Inbox "Hubungi Kami" (PRD 8.5). */
class ContactMessageController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:contact-message-view')->only(['index', 'datatable']);
        $this->authorizeResource(ContactMessage::class, 'contact_message');
    }

    public function index()
    {
        return view('dashboard.contact-message.index', ['unread' => ContactMessage::whereNull('read_at')->count()]);
    }

    public function datatable(Request $request)
    {
        return DataTables::eloquent(ContactMessage::query())
            ->editColumn('created_at', fn ($m) => $m->created_at->translatedFormat('d M Y H:i'))
            ->addColumn('state', fn ($m) => $m->read_at ? 'Dibaca' : '<span class="badge badge-light-danger">Baru</span>')
            ->addColumn('action', fn ($m) => view('components.dashboard.row-actions', [
                'show' => route('contact-message.show', $m), 'edit' => null,
                'delete' => $request->user()->can('delete', $m) ? route('contact-message.destroy', $m) : null, 'confirm' => null,
            ])->render())
            ->rawColumns(['state', 'action'])
            ->addIndexColumn()
            ->make(true);
    }

    public function show(ContactMessage $contactMessage)
    {
        $contactMessage->read_at ??= now();
        $contactMessage->save();

        return view('dashboard.contact-message.show', ['message' => $contactMessage]);
    }

    public function destroy(ContactMessage $contactMessage)
    {
        $contactMessage->delete();
        session()->flash('success', 'Pesan dihapus.');

        return response()->noContent();
    }
}
