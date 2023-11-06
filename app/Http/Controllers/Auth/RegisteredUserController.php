<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Mail\SendOtp;
use App\Mail\LoginMail;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rules;
use App\Http\Controllers\Controller;
use App\Models\Invitation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): Response
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', Rules\Password::defaults()],
            // 'code' => ['exists:users,id']
        ]);

        if (!is_null($request['code'])) {
            $request->validate([
                'code' => 'exists:users,id'
                ]);
            if (DB::table('users')->where('parent_id', $request->code)->count() >= 4) {
                return response("Enter another code.", 400);
            }
            $on_hold = 1;
        } else {
            $on_hold = 0;
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'parent_id' => $request->code ?? null,
            'on_hold' => $on_hold,
            'password' => Hash::make($request->password),
        ])->assignRole('user');

        event(new Registered($user));

        // Auth::login($user);

        return response()->noContent();
    }

    public function updateUser(Request $request)
    {

        if ($request->hasFile('attachment1')) {
            $attachment1 = $request->file('attachment1')->store('attachment1');
        }
        if ($request->hasFile('attachment2')) {
            $attachment2 = $request->file('attachment2')->store('attachment2');
        }
        $user = User::find(auth()->user()->id);
        $name = $request['firstName'] . " " . $request['middleName'] . " " . $request['lastName'];
        $user = User::where('id', auth()->user()->id)->update([
            'name' => $request['name'],
            'first_name' => $request['firstName'],
            'middle_name' => $request['middleName'],
            'last_name' => $request['lastName'],
            'gender' => $request['gender'],
            'phone_number' => $request['phone'],
            'micr' => $request['micr'] ?? $user->micr,
            'email' => $request['email'],
            'dob' => $request['dob'],
            'attachment_1' => $attachment1 ?? null,
            'attachment_2' => $attachment2 ?? null,
            'account_number' => $request->accountNumber ?? $user->account_number,
            'ifsc' => $request->ifsc ?? $user->ifsc,
            'upi_id' => $request->upi ?? $user->upi_id,
            'bank_name' => $request->bankName ?? $user->bank_name,
        ]);

        return $user;
    }

    public function invitationMail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'url' => 'required|url'
        ]);

        $email = $request['email'];
        $url = $request['url'];
        $name = $request['name'];
        $phone_number = $request['phone'];
        if (DB::table('users')->where('email', $email)->exists()) {
            return response("User already exists.", 404);
        }
        Invitation::updateOrInsert(
            [
                'email' => $email,
            ],
            [
                'message' => $url,
                'phone_number' => $phone_number,
                'name' => $name,
            ]
        );
        Mail::to($email)->send(new LoginMail($name, $url));
        return response()->noContent();
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $otp = rand(1001, 9999);
        $email = $request['email'];
        $user = User::where('email', $email)->update(['otp' => Hash::make($otp), 'otp_generatd_at' => now()]);

        Mail::to($email)->send(new SendOtp($otp));
        return response()->noContent();
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|numeric|max:4'
        ]);

        $otp = $request['otp'];
        $email = $request['email'];
        $user = User::where('email', $email)->first();
        $otp_generated_at = Carbon::parse($user->otp_generated_at);
        $current_time = Carbon::parse(now());
        $difference = $current_time->diffInRealMinutes($otp_generated_at);
        if ($difference > 5) {
            throw ValidationException::withMessages([
                'message' => ['OTP Expired.'],
            ]);
        }
        if (!$user || !Hash::check($otp, $user->otp)) {
            throw ValidationException::withMessages([
                'message' => ['You have entered wrong OTP.'],
            ]);
        }
        $user->update(['email_verified_at' => now()]);
        return response()->noContent();
    }
}
