<?php

namespace CodeZone\socialite\drivers;

use craft\elements\User;
use craft\helpers\Assets;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;

class GoogleDriver extends Driver
{

    /**
     * Instantiate the provider.
     * @param array $config
     * @return AbstractProvider
     */
    protected function provider(array $config): AbstractProvider {
        return new Google($config);
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
            'email' => 'email',
            'username' => 'email'
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
     * Sync the users primary 'email' from google
     * @param User $user
     * @param AccessToken $token
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     */
    public function syncEmail(User $user, AccessToken $token)
    {
        $email = $this->getProvider()->getResourceOwner($token)->getEmail();
        if ($email) {
            if (\Craft::$app->getUsers()->getUserByUsernameOrEmail($email)) {
                return;
            }
        }
        $user->email = $email;
        \Craft::$app->getElements()->saveElement($user);
    }

    /**
     * Sync the users photo from google
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

        $owner = $this->getProvider()->getResourceOwner($token);
        $url = $owner->getAvatar();

        if (!$url) {
            return;
        }

        try {
            $rawImage = file_get_contents($url);
        } catch (\Exception $ex) {
            $rawImage = null;
        }

        if (!$rawImage) {
            return;
        }

        $extension = substr(strrchr($url,'.'),1);
        $filename = 'google-profile-' . $user->id . '.' . $extension;

        $fileLocation = Assets::tempFilePath($extension);
        file_put_contents($fileLocation, $rawImage);
        \Craft::$app->getUsers()->saveUserPhoto($fileLocation, $user, $filename);
    }
}