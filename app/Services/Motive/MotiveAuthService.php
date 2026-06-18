<?php

namespace App\Services\Motive;

use Illuminate\Support\Facades\Http;
use App\Models\Motive\MotiveToken;

class MotiveAuthService
{
    public function authorizationUrl()
    {
        return config('motive.auth_url')
            .'/authorize?'
            .http_build_query([
                'client_id'     => config('motive.client_id'),
                'redirect_uri'  => config('motive.redirect_uri'),
                'response_type' => 'code',
                'scope'         => 'vehicles.read users.read',
            ]);
    }

    public function storeToken($code)
    {
        $response = Http::asForm()->post(
            config('motive.auth_url').'/token',
            [
                'grant_type'    => 'authorization_code',
                'code'          => $code,
                'redirect_uri'  => config('motive.redirect_uri'),
                'client_id'     => config('motive.client_id'),
                'client_secret' => config('motive.client_secret'),
            ]
        );

        if (!$response->successful()) {

            throw new \Exception($response->body());
        }

        $data = $response->json();

        MotiveToken::updateOrCreate(
            ['company_id' => 1],
            [
                'access_token' => $data['access_token'],
                'refresh_token'=> $data['refresh_token'],
                'expires_at'   => now()->addSeconds($data['expires_in']),
            ]
        );

        return true;
    }
}