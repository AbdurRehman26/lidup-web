<?php

namespace App\Http\Controllers;

use App\Models\AppActivation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function destroy(Request $request, AppActivation $activation): RedirectResponse
    {
        abort_unless($activation->user_id === $request->user()->id, 404);

        $activation->update(['revoked_at' => now()]);

        return back()->with('device_message', 'The Mac has been deactivated.');
    }
}
