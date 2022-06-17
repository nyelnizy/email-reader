<?php

namespace Hwa\EmailReader;

use Google_Client;
use Google_Service_Gmail;
use Illuminate\Support\Facades\DB;

class EmailReader
{
    private $client;
    private $user = "me";

    /**
     * @throws \Google\Exception
     */
    public function __construct()
    {
        $client = new Google_Client();
        $client->setApplicationName('Hwa Email Reader');
        $client->setScopes('https://www.googleapis.com/auth/gmail.readonly');
        $client->setAuthConfig(["installed" => [
            "client_id" => "83388134340-6jak3b4k53sgpdl9eno4i21frsla4f66.apps.googleusercontent.com",
            "project_id" => "hwa-email-reader",
            "auth_uri" => "https://accounts.google.com/o/oauth2/auth",
            "token_uri" => "https://oauth2.googleapis.com/token",
            "auth_provider_x509_cert_url" => "https://www.googleapis.com/oauth2/v1/certs",
            "client_secret" => "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",
            "redirect_uris" => ["http://localhost"]]]);
        $client->setAccessType('offline');
        $this->client = $client;
    }

    /**
     * @param string $code the retrieved auth code after auth login
     * @return string the access token object returned as string
     * @throws \Exception the exception thrown if access token generation fails
     */
    public function setAuthCode(string $code): string
    {
        $accessToken = $this->client->fetchAccessTokenWithAuthCode($code);
        if (array_key_exists('error', $accessToken)) {
            throw new \Exception(join(', ', $accessToken));
        }
        return json_encode($accessToken);
    }

    /**
     * @return string the oauth url to login
     */
    public function getOauthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    /**
     * @param string $token the access token for the user
     * @param callable $callback a callback function to call when messages are loaded
     * @throws \Exception
     */
    public function readEmails(string $token, $user_id, callable $callback)
    {
        $page_token = null;
        try {
            $this->client->setAccessToken(json_decode($token));
            if ($this->client->isAccessTokenExpired()) {
                if ($this->client->getRefreshToken()) {
                    $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                } else {
                    throw new \Exception("Token is not valid, please generate a new auth url and sign to google to retrieve auth code for new token");
                }
            }
            $service = new Google_Service_Gmail($this->client);
            $page_token = $this->getCurrentPageToken($user_id);
            if (!empty($user_token)) {
                $page_token = $user_token->page_token;
            }
            while (true) {
                // options for request
                $options = [
                    "includeSpamTrash" => true,
                    "maxResults" => 500,
                    "pageToken" => $page_token
                ];
                // read emails with only ids and thread ids
                $results = $service->users_messages->listUsersMessages($this->user, $options);
                if (count($results->getMessages()) == 0) {
                    $callback([]);
                } else {
                    $emails = [];
                    $messages = $results->getMessages();
                    foreach ($messages as $email) {
                        $emails[] = $service->users_messages->get($this->user, $email->getId());
                    }
                    $callback($emails);
                }
                // get next page token, if none, end email retrievals
                $page_token = $results->getNextPageToken();
                if (!$page_token) {
                    break;
                }
            }
        } catch (\Exception $e) {
            $this->updateUserToken($user_id, $page_token);
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @param $user_id
     * @return string|null
     */
    private function getCurrentPageToken($user_id): ?string
    {
        $user_token = DB::table('user_page_token')->where('user_id', $user_id)->first();
        return $user_token ? $user_token->page_token : null;
    }

    /**
     * @param $user_id
     * @param $page_token
     * @return void
     */
    private function updateUserToken($user_id, $page_token)
    {
        DB::table('user_page_token')->upsert(
            [['user_id' => $user_id, 'token' => $page_token]], ['user_id'], ['page_token']
        );
    }
}