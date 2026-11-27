<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\PasswordResetCodeNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    private const CODE_LIFETIME_MINUTES = 15;

    /**
     * يرسل كود من 6 أرقام. نفس الرد سواء البريد مسجّل أو لا، عشان ما نكشف الحسابات.
     */
    public function forgot(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $data['email'])->first();

        if ($user !== null) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                ['token' => Hash::make($code), 'created_at' => now()],
            );

            Notification::route('mail', $user->email)->notify(new PasswordResetCodeNotification($code));
        }

        return response()->json(['message' => 'إذا البريد مسجّل، راح يوصلك كود استعادة.']);
    }

    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $row = DB::table('password_reset_tokens')->where('email', $data['email'])->first();
        $expired = $row === null
            || Carbon::parse($row->created_at)->lt(now()->subMinutes(self::CODE_LIFETIME_MINUTES));

        if ($expired || ! Hash::check($data['code'], $row->token)) {
            throw ValidationException::withMessages(['code' => ['الكود غلط أو انتهت صلاحيته.']]);
        }

        $user = User::where('email', $data['email'])->firstOrFail();
        $user->update(['password' => $data['password']]);
        $user->tokens()->delete();

        DB::table('password_reset_tokens')->where('email', $data['email'])->delete();

        return response()->json(['ok' => true]);
    }
}
