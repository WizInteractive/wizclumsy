<?php

namespace Wizclumsy\CMS\Controllers;

use Carbon\Carbon;
use Wizclumsy\CMS\Facades\Overseer;
use Wizclumsy\CMS\Facades\Clumsy;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    use AuthenticatesUsers, ValidatesRequests;

    protected $username;

    protected $routePrefix;
    protected $loginPath;
    protected $redirectPath;
    protected $redirectAfterLogout;

    protected $maxLoginAttempts = INF;
    protected $lockoutTime;

    public function __construct()
    {
        $this->routePrefix = config('clumsy.cms.authentication-prefix');

        $this->loginPath = "{$this->routePrefix}/login";
        $this->redirectAfterLogout = $this->loginPath;

        if ($this->throttles()) {
            $this->maxLoginAttempts = config('clumsy.cms.throttling-max-attempts');
            $this->lockoutTime = config('clumsy.cms.throttling-lockout-time');
        }
    }

    protected function throttles()
    {
        return config('clumsy.cms.authentication-throttling');
    }

    protected function authenticationAttributes()
    {
        return array_merge((array)config('clumsy.cms.authentication-attributes'), ['password']);
    }

    protected function credentials(Request $request)
    {
        return $request->only($this->authenticationAttributes());
    }

    public function redirectPath()
    {
        return Clumsy::prefix();
    }

    /**
     * Check if the current user is logged in
     *
     * @return string
     */
    public function isLoggedIn()
    {
        if (Overseer::check()) {
            return 'user';
        }

        return 'guest';
    }

    /**
     * Show the application login form.
     *
     * @return \Illuminate\Http\Response
     */
    public function getLogin()
    {
        if (Overseer::check()) {
            return redirect($this->redirectPath());
        }

        $data['bodyClass'] = 'login';

        return view('clumsy::auth.login', $data);
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function postLogin(Request $request)
    {
        $rules = array_fill_keys($this->authenticationAttributes(), 'required');
        $this->validate($request, $rules, trans('clumsy::alerts.auth.validate'));

        $throttles = $this->throttles();

        if ($throttles && $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        $credentials = $this->credentials($request);
        if (Overseer::attempt($credentials, $request->has('remember'))) {
            return $this->sendLoginResponse($request);
        }

        if ($throttles) {
            $this->incrementLoginAttempts($request);
        }

        return redirect()->back()
            ->withInput($request->except('password'))
            ->withAlert([
                'warning' => trans('clumsy::alerts.auth.failed'),
            ]);
    }

    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        $user->last_login = Carbon::now();
        $user->save();
    }

    /**
     * Get the login lockout error message.
     *
     * @param  int  $seconds
     * @return string
     */
    protected function getLockoutErrorMessage($seconds)
    {
        return trans('clumsy::alerts.auth.lockout', compact('seconds'));
    }

    public function reset()
    {
        if (Overseer::check()) {
            return redirect($this->redirectPath());
        }

        $data['bodyClass'] = 'login';

        return view('clumsy::auth.reset', $data);
    }

    public function postReset(Request $request)
    {
        if (!request()->get('email')) {
            $alert = trans('clumsy::alerts.auth.login_required');

        } else {

            $response = Overseer::password()->sendResetLink($request->only('email'), function (Message $message) {
                $message->subject(trans('clumsy::titles.reset-password'));
            });

            switch ($response) {
                case 'passwords.sent':
                    if (count(Mail::failures())) {
                        $alert = trans('clumsy::alerts.email-error');
                    } else {
                        $alertStatus = 'success';
                        $alert = trans('clumsy::alerts.auth.reset-email-sent');
                    }
                    break;

                case 'passwords.user':
                    $alert = trans('clumsy::alerts.auth.unknown-user');
                    break;
            }
        }

        return back()->withInput()->withAlert([
            isset($alertStatus) ? $alertStatus : 'warning' => $alert,
        ]);
    }

    public function doReset($token)
    {
        if (Overseer::check()) {
            return redirect($this->redirectPath());
        }

        $bodyClass = 'login';

        return view('clumsy::auth.do-reset', compact('bodyClass', 'token'));
    }

    public function postDoReset($token, Request $request)
    {
        $this->validate($request, [
            'email'    => 'required|email',
            'password' => 'required|confirmed|min:6|max:191',
        ]);

        $credentials = array_merge(compact('token'), $request->only(
            'email', 'password', 'password_confirmation'
        ));

        $response = Overseer::password()->reset($credentials, function ($user, $password) {
            $user->password = $password;
            $user->save();
            Overseer::login($user);
        });

        switch ($response) {
            case 'passwords.reset':
                return redirect($this->redirectPath())->withAlert([
                    'success' => trans('clumsy::alerts.auth.password-changed'),
                ]);

            case 'passwords.user':
                $alert = trans('clumsy::alerts.auth.unknown-user');
                break;

            default:
                $alert = trans('clumsy::alerts.auth.reset-error');
        }

        return back()->withInput($request->only('email'))->withAlert([
            'warning' => $alert,
        ]);
    }

    /**
     * Log the user out of the application.
     *
     * @return \Illuminate\Http\Response
     */
    public function getLogout()
    {
        Overseer::logout();

        return redirect(property_exists($this, 'redirectAfterLogout') ? $this->redirectAfterLogout : '/')->withAlert([
            'success' => trans('clumsy::alerts.auth.logged-out')
        ]);
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Overseer::guard();
    }
}
