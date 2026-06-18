<?php

namespace App\Services\Motive;

use Illuminate\Support\Facades\Http;
use App\Models\Motive\MotiveToken;

class MotiveApiService
{
    protected $token;

    public function __construct()
    {
        $this->token = MotiveToken::first();
    }

    public function token()
    {
        if(now()->greaterThan($this->token->expires_at)){

            $this->refreshToken();
        }

        return $this->token->fresh()->access_token;
    }

    public function refreshToken()
    {
        $response = Http::asForm()->post(
            config('motive.auth_url').'/token',
            [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $this->token->refresh_token,
                'client_id'     => config('motive.client_id'),
                'client_secret' => config('motive.client_secret'),
            ]
        );

        $data = $response->json();

        $this->token->update([
            'access_token' => $data['access_token'],
            'refresh_token'=> $data['refresh_token'],
            'expires_at'   => now()->addSeconds($data['expires_in']),
        ]);
    }

    public function get($endpoint, $params = [])
    {
        return Http::retry(3, 1000)
            ->withToken($this->token())
            ->acceptJson()
            ->get(config('motive.base_url').$endpoint, $params)
            ->json();
    }
}