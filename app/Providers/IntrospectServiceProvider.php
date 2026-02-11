<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class IntrospectServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        Auth::viaRequest('LAMB_AUTH', function (Request $request) {
            $access_token = self::getTokenForRequest($request);
            return self::getUser($access_token);
        });
    }


    protected function getTokenForRequest(Request $request)
    {
        $token = $request->query('api_token');
        if (empty($token)) {
            $token = $request->input('api_token');
        }

        if (empty($token)) {
            $token = $request->bearerToken();
        }

        if (empty($token)) {
            $token = $request->header('Authorization');
        }

        if (empty($token)) {
            abort(401, 'Token required.',
                ['WWW-Authenticate' => 'send a token of either api token type in query params or bearer type on the header']);
        }

        return $token;
    }

    protected function getUser($access_token)
    {
        $response = Http::asForm()
            ->withToken(env('LAMB_AUTH_INTROSPECT_TOKEN', ''))
            ->post(env('LAMB_AUTH_INTROSPECT_ENDPOINT'), ['token' => $access_token]);

        switch ($response->status()) {
            case 200:
                if ($response->json()['active']) {
                    return User::where('email', $response->json()['username'])
                        ->first();
                } else {
                    abort(403, 'Permission denied.');
                }
            case 401:
                abort(401, 'Invalid token.');
            case 403:
                abort(403, 'Permission denied.');
            default:
                abort(500, 'Auth error.');

        }

    }
}
