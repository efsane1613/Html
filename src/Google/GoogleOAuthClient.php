<?php

namespace App\Google;

use RuntimeException;

class GoogleOAuthClient
{
    private const AUTH_ENDPOINT = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';
    private const DEFAULT_REDIRECT_URI = 'https://developers.google.com/oauthplayground';
    private const DEFAULT_SCOPES = ['https://www.googleapis.com/auth/business.manage'];

    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    public function __construct(string $clientId, string $clientSecret, ?string $redirectUri = null)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri ?: self::DEFAULT_REDIRECT_URI;
    }

    public function buildAuthorizationUrl(?string $state = null, array $scopes = self::DEFAULT_SCOPES): string
    {
        if (empty($scopes)) {
            $scopes = self::DEFAULT_SCOPES;
        }

        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'access_type' => 'offline',
            'prompt' => 'consent',
        ];

        if ($state !== null && $state !== '') {
            $params['state'] = $state;
        }

        return self::AUTH_ENDPOINT . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @return array{access_token:string, refresh_token?:string, expires_in?:int}
     */
    public function exchangeAuthorizationCode(string $authorizationCode): array
    {
        $payload = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $authorizationCode,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
        ];

        return $this->requestToken($payload);
    }

    /**
     * @return array{access_token:string, refresh_token?:string, expires_in?:int}
     */
    public function refreshAccessToken(string $refreshToken): array
    {
        $payload = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ];

        return $this->requestToken($payload);
    }

    /**
     * @param array<string, string> $payload
     *
     * @return array{access_token:string, refresh_token?:string, expires_in?:int}
     */
    private function requestToken(array $payload): array
    {
        $ch = curl_init();
        if ($ch === false) {
            throw new RuntimeException('Unable to initialise cURL.');
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => self::TOKEN_ENDPOINT,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_POSTFIELDS => http_build_query($payload, '', '&', PHP_QUERY_RFC3986),
        ]);

        $result = curl_exec($ch);
        if ($result === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException(sprintf('Google OAuth request failed: %s', $error));
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status >= 400) {
            throw new RuntimeException(sprintf('Google OAuth request returned status %d: %s', $status, $result));
        }

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($result, true);
        if (!is_array($decoded) || empty($decoded['access_token'])) {
            throw new RuntimeException('Google OAuth request did not return a valid token response.');
        }

        $response = [
            'access_token' => (string)$decoded['access_token'],
        ];

        if (!empty($decoded['refresh_token'])) {
            $response['refresh_token'] = (string)$decoded['refresh_token'];
        }

        if (isset($decoded['expires_in'])) {
            $response['expires_in'] = (int)$decoded['expires_in'];
        }

        return $response;
    }
}
