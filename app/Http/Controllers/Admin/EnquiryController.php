<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnquiryController extends Controller
{
    public function index(Request $request): View
    {
        $query = Enquiry::query();

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $enquiries = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total' => Enquiry::count(),
            'new' => Enquiry::where('status', 'new')->count(),
            'read' => Enquiry::where('status', 'read')->count(),
            'replied' => Enquiry::where('status', 'replied')->count(),
        ];

        return view('admin.enquiries.index', compact('enquiries', 'stats'));
    }

    public function show(Enquiry $enquiry): View
    {
        $enquiry->markAsRead();

        // Mark related notifications as read
        Notification::where('type', 'new_enquiry')
            ->where('data->enquiry_id', $enquiry->id)
            ->unread()
            ->update(['is_read' => true, 'read_at' => now()]);

        return view('admin.enquiries.show', compact('enquiry'));
    }

    public function toggleRead(Enquiry $enquiry): RedirectResponse
    {
        if ($enquiry->is_read) {
            $enquiry->update([
                'is_read' => false,
                'read_at' => null,
                'status' => 'new',
            ]);
        } else {
            $enquiry->markAsRead();
        }

        return back()->with('success', $enquiry->is_read ? 'Marked as read.' : 'Marked as unread.');
    }

    public function updateStatus(Request $request, Enquiry $enquiry): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:new,read,replied,closed'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $enquiry->update([
            'status' => $request->input('status'),
            'admin_notes' => $request->input('admin_notes'),
        ]);

        return back()->with('success', 'Enquiry status updated.');
    }

    public function destroy(Enquiry $enquiry): RedirectResponse
    {
        $enquiry->delete();

        return redirect()->route('admin.enquiries.index')->with('success', 'Enquiry deleted.');
    }
}
