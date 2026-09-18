<?php
namespace Dth\AccountManagement\Http\Controllers;

use Dth\AccountManagement\Services\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class InvitationAcceptanceController extends Controller
{
    public function show(string $token, InvitationService $service): View
    {
        try {
            $invitation = $service->findByToken($token);
        } catch (Throwable) {
            abort(404);
        }
        return view('dth-account-management::invitations.accept', compact('invitation', 'token'));
    }

    public function accept(Request $request, string $token, InvitationService $service): RedirectResponse
    {
        try {
            $invitation = $service->findByToken($token);
        } catch (Throwable) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required','string','max:255'],
            'password' => ['required','string','min:10','confirmed'],
        ]);
        $user = $service->accept($invitation, $validated['name'], $validated['password']);
        Auth::loginUsingId($user->id);
        return redirect('/admin');
    }
}
