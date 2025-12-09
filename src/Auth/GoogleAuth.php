<?php

namespace App\Auth;

use Google\Client as GoogleClient;
use Exception;

class GoogleAuth
{
    private $client;
    private $redirectUri;

    public function __construct()
    {
        $this->client = new GoogleClient();

        // Try to load from JSON file first
        $jsonPath = __DIR__ . '/../../google-credentials.json';

        if (file_exists($jsonPath)) {
            // Load from JSON file
            $this->client->setAuthConfig($jsonPath);
            $this->client->setRedirectUri($this->getRedirectUri());
        } else {
            // Use environment variables from Docker/EasyPanel
            $clientId = getenv('GOOGLE_CLIENT_ID') ?: ($_ENV['GOOGLE_CLIENT_ID'] ?? '');
            $clientSecret = getenv('GOOGLE_CLIENT_SECRET') ?: ($_ENV['GOOGLE_CLIENT_SECRET'] ?? '');
            $redirectUri = getenv('GOOGLE_REDIRECT_URI') ?: ($_ENV['GOOGLE_REDIRECT_URI'] ?? '');

            $this->client->setClientId($clientId);
            $this->client->setClientSecret($clientSecret);
            $this->redirectUri = $redirectUri;
            $this->client->setRedirectUri($this->redirectUri);
        }

        $this->client->addScope("email");
        $this->client->addScope("profile");
    }

    /**
     * Get the redirect URI based on current environment
     */
    private function getRedirectUri(): string
    {
        // Use environment variable if set (recommended for production)
        $envRedirectUri = getenv('GOOGLE_REDIRECT_URI') ?: ($_ENV['GOOGLE_REDIRECT_URI'] ?? '');
        if (!empty($envRedirectUri)) {
            return $envRedirectUri;
        }

        // Auto-detect for local development
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // Check if running locally or in production
        if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
            // Local development
            return $protocol . "://" . $host . "/projeto-pizzaria/public/google-callback.php";
        } else {
            // Production (EasyPanel/Docker)
            return $protocol . "://" . $host . "/google-callback.php";
        }
    }

    /**
     * Load .env file into $_ENV (fallback method)
     */
    private function loadEnv()
    {
        $envPath = __DIR__ . '/../../.env';

        if (!file_exists($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove quotes if present
                $value = trim($value, '"\'');

                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }

    /**
     * Get the Google OAuth URL to redirect user
     */
    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    /**
     * Handle the OAuth callback and get user info
     */
    public function handleCallback(string $code): ?array
    {
        try {
            // Exchange authorization code for access token
            $token = $this->client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                throw new Exception('Error fetching access token: ' . $token['error']);
            }

            $this->client->setAccessToken($token);

            // Get user profile info
            $google_oauth = new \Google\Service\Oauth2($this->client);
            $google_account_info = $google_oauth->userinfo->get();

            return [
                'id' => $google_account_info->id,
                'email' => $google_account_info->email,
                'name' => $google_account_info->name,
                'picture' => $google_account_info->picture,
                'verified_email' => $google_account_info->verifiedEmail
            ];

        } catch (Exception $e) {
            error_log("Google OAuth Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if credentials are configured
     */
    public function isConfigured(): bool
    {
        $jsonPath = __DIR__ . '/../../google-credentials.json';
        if (file_exists($jsonPath)) {
            return true;
        }

        // Check environment variables from Docker/EasyPanel
        $clientId = getenv('GOOGLE_CLIENT_ID') ?: ($_ENV['GOOGLE_CLIENT_ID'] ?? '');
        $clientSecret = getenv('GOOGLE_CLIENT_SECRET') ?: ($_ENV['GOOGLE_CLIENT_SECRET'] ?? '');
        $redirectUri = getenv('GOOGLE_REDIRECT_URI') ?: ($_ENV['GOOGLE_REDIRECT_URI'] ?? '');

        return !empty($clientId) && !empty($clientSecret) && !empty($redirectUri);
    }
}
