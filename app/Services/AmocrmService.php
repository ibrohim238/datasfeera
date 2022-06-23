<?php

namespace App\Services;

use AmoCRM\Client\AmoCRMApiClient;
use Exception;

class AmocrmService
{
    public AmoCRMApiClient $amoCRMApiClient;

    public function __construct()
    {
        $this->amoCRMApiClient = new AmoCRMApiClient(
            config('services.amocrm.clientId'),
            config('services.amocrm.clientSecret'),
            config('services.amocrm.clientRedirectUrl')
        );
    }

    public function getToken()
    {
        if (isset($_GET['referer'])) {
            $this->amoCRMApiClient->setAccountBaseDomain($_GET['referer']);
        }


        if (!isset($_GET['code'])) {
            $state = bin2hex(random_bytes(16));
            $_SESSION['oauth2state'] = $state;
            if (isset($_GET['button'])) {
                echo $this->amoCRMApiClient->getOAuthClient()->getOAuthButton(
                    [
                        'title' => 'Установить интеграцию',
                        'compact' => true,
                        'class_name' => 'className',
                        'color' => 'default',
                        'error_callback' => 'handleOauthError',
                        'state' => $state,
                    ]
                );
                die;
            } else {
                $authorizationUrl = $this->amoCRMApiClient->getOAuthClient()->getAuthorizeUrl([
                    'state' => $state,
                    'mode' => 'post_message',
                ]);
                header('Location: ' . $authorizationUrl);
                die;
            }
        } elseif (empty($_GET['state']) || empty($_SESSION['oauth2state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
            unset($_SESSION['oauth2state']);
            exit('Invalid state');
        }

        /**
         * Ловим обратный код
         */
        try {
            $accessToken = $this->amoCRMApiClient->getOAuthClient()->getAccessTokenByCode($_GET['code']);

            if (!$accessToken->hasExpired()) {
                saveToken([
                    'accessToken' => $accessToken->getToken(),
                    'refreshToken' => $accessToken->getRefreshToken(),
                    'expires' => $accessToken->getExpires(),
                    'baseDomain' => $this->amoCRMApiClient->getAccountBaseDomain(),
                ]);
            }
        } catch (Exception $e) {
            die((string)$e);
        }

        $ownerDetails = $this->amoCRMApiClient->getOAuthClient()->getResourceOwner($accessToken);

        printf('Hello, %s!', $ownerDetails->getName());
    }
}
