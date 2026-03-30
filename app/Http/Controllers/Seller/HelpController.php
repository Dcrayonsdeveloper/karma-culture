<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HelpController extends Controller
{
    public function index(): View
    {
        return view('seller.help.index');
    }

    public function contact(): View
    {
        return view('seller.help.contact');
    }

    public function submitContact(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:general,products,orders,payments,account,other'],
            'message' => ['required', 'string', 'min:10'],
        ]);

        SupportTicket::create([
            'user_id' => auth()->id(),
            'subject' => $validated['subject'],
            'category' => $validated['category'],
            'message' => $validated['message'],
        ]);

        return redirect()->route('seller.help')->with('success', 'Your support ticket has been submitted. We\'ll get back to you soon.');
    }
}
