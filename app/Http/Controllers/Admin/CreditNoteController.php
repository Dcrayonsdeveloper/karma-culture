<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditNote;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CreditNoteController extends Controller
{
    public function index(Request $request): View
    {
        $query = CreditNote::with(['user', 'order', 'return']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('credit_note_number', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($uq) => $uq->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%"))
                  ->orWhereHas('order', fn($oq) => $oq->where('order_number', 'like', "%{$search}%"));
            });
        }

        $perPage = $request->input('per_page', 10);
        $creditNotes = $query->latest()->paginate($perPage)->withQueryString();

        $stats = [
            'total' => CreditNote::count(),
            'active' => CreditNote::where('status', 'active')->count(),
            'total_amount' => CreditNote::sum('amount'),
            'total_remaining' => CreditNote::where('status', 'active')->sum('remaining_amount'),
        ];

        return view('admin.credit-notes.index', compact('creditNotes', 'stats'));
    }

    public function show(CreditNote $creditNote): View
    {
        $creditNote->load(['user', 'order', 'return.items.orderItem.product', 'usages.order']);

        return view('admin.credit-notes.show', compact('creditNote'));
    }
}
