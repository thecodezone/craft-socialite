<?php


namespace CodeZone\socialite\drivers;


use CodeZone\socialite\Socialite;
use craft\helpers\Assets;
use craft\web\Request;
use craft\elements\User;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use TheNetworg\OAuth2\Client\Provider\Azure;
use Mimey\MimeTypes;

class AzureDriver extends Driver
{
    /**
     * Instantiate the provider.
     * @param array $config
     * @return AbstractProvider
     */
    protected function provider(array $config): AbstractProvider
    {
        $provider = new Azure($config);
        $provider->urlAPI = "https://graph.microsoft.com/v1.0/";
        $provider->resource = "https://graph.microsoft.com/";
        return $provider;
    }

    /**
     * Return an array that maps user field keys to provider field keys.
     * @return array
     */
    protected function userFieldMap(): array
    {
        return [
            'firstName' => 'given_name',
            'lastName' => 'family_name',
            'email' => 'upn',
            'username' => 'upn'
        ];
    }

    /**
     * Do any additional steps the driver needs to do after a user is logged in.
     * @param $accessToken
     * @param $user
     * @param $ssoAccount
     */
    public function cleanup(User $user, AccessToken $token)
    {
        $this->syncPhoto($user, $token);
        $this->syncEmail($user, $token);
    }

    /**
     * Sync the users primary email from azure
     * @param User $user
     * @param AccessToken $token
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     */
    public function syncEmail(User $user, AccessToken $token)
    {
        $me = $this->getProvider()->get('me?$select=mail', $token);
        if ($me && $me['mail']) {
            if (\Craft::$app->getUsers()->getUserByUsernameOrEmail($me['mail'])) {
                return;
            }
        }
        $user->email = $me['mail'];
        \Craft::$app->getElements()->saveElement($user);
    }

    /**
     * Sync the users photo from azure
     * @param User $user
     * @param AccessToken $token
     * @throws \craft\errors\ImageException
     * @throws \craft\errors\VolumeException
     * @throws \yii\base\Exception
     */
    public function syncPhoto(User $user, AccessToken $token)
    {
        if ($user->photo) {
            return;
        }

        $rawImage = $this->getProvider()->get('me/photo/$value', $token);

        if (!$rawImage) {
            return;
        }

        $mimes = new MimeTypes;
        $imageMeta = $this->getProvider()->get('me/photo', $token);

        $mime = $imageMeta["@odata.mediaContentType"];
        $extension = $mimes->getExtension($mime);
        $filename = 'azure-profile-' . $user->id . '.' . $extension;

        $fileLocation = Assets::tempFilePath($extension);
        file_put_contents($fileLocation, $rawImage);
        \Craft::$app->getUsers()->saveUserPhoto($fileLocation, $user, $filename);
    }

    public function get($endpoint, $token = null)
    {
        if (!$token) {
            $token = $this->getAccessToken();
        }

        if (!$token) {
            return false;
        }

        return $this->getProvider()->get($endpoint, $token);
    }
}