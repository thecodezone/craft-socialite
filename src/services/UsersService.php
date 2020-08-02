<?php


namespace CodeZone\socialite\services;


use Adbar\Dot;
use CodeZone\socialite\drivers\DriverContract;
use CodeZone\socialite\records\SSOAccountsRecord;
use craft\base\Component;
use craft\elements\User;
use League\OAuth2\Client\Token\AccessToken;
use Craft;

class UsersService extends Component
{
    /**
     * Find or create a user from a token
     */
    public function fromToken(DriverContract $driver, AccessToken $token)
    {
        $ssoId = $driver->getOwnerId($token);

        $account = SSOAccountsRecord::find()->where([
            'ssoId' => $ssoId,
            'provider' => $driver::slug()
        ])->one();

        if (!$account) {
            $account = new SSOAccountsRecord();
            $account->ssoId = $ssoId;
            $account->provider = $driver::slug();
        }


        if (!$account->userId) {
            $user = $this->create($driver, $token);
        } else {
            $user = Craft::$app->users->getUserById($account->userId);
        }

        $this->populate($user, $driver, $token);
        Craft::$app->elements->saveElement($user);
        $user = Craft::$app->users->getUserByUsernameOrEmail($user->email);
        $account->userId = $user->id;

        $this->syncSsoAccount($account, $token);

        return $user;
    }

    public function syncSsoAccount(SSOAccountsRecord $account, AccessToken $token)
    {
        $refreshToken = $token->getRefreshToken();
        $accessToken = $token->getToken();
        $account->token = $accessToken;
        $account->refreshToken = $refreshToken ? $refreshToken : $accessToken;
        $account->expiresAt = $token->getExpires();
        $account->save();
    }

    /**
     * New up a user using the email and username from the account owner.
     *
     * @param DriverContract $driver
     * @param AccessToken $token
     * @return User
     */
    protected function create(DriverContract $driver, AccessToken $token)
    {
        $user = new User();
        $map = $driver->getUserFieldMap();
        $owner =  new Dot($driver->getOwner($token)->toArray());

        $user->email = $owner->get($map['email']);
        $user->username = ($owner->has($map['username']) || Craft::$app->getConfig()->getGeneral()->useEmailAsUsername) ? $owner->get($map['username']) : $owner->get($map['email']);

        return $user;
    }

    /**
     * Fill a user using data from the account owner.
     *
     * @param User $user
     * @param $driver
     * @param $token
     * @return User
     */
    protected function populate(User $user, $driver, $token)
    {
        $owner = $driver->getOwner($token)->toArray();
        $map = $driver->getUserFieldMap();
        $restricted = ['email', 'username', 'preferredLocale', 'weekStartDay'];
        $tableFields = ['firstName', 'lastName'];
        $dot = new Dot($owner);

        foreach ($map as $field => $dotNotation) {
            if (!in_array($field, $restricted) && $dot->has($dotNotation)) {
                if (in_array($field, $tableFields)) {
                    $user->$field = $dot->get($dotNotation);
                } else {
                    $user->setFieldValue($field, $dot->get($dotNotation));
                }
            }
        }

        return $user;
    }
}