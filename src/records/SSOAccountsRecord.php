<?php
/**
 * socialite plugin for Craft CMS 3.x
 *
 * Login to Craft with third-party services like Azure and Google. 
 *
 * @link      https://codezone.io
 * @copyright Copyright (c) 2020 CodeZone
 */

namespace CodeZone\socialite\records;

use CodeZone\socialite\Socialite;

use Craft;
use craft\db\ActiveRecord;

/**
 * @author    CodeZone
 * @package   Socialite
 * @since     0.0.0
 */
class SSOAccountsRecord extends ActiveRecord
{
    // Public Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%socialite_sso_accounts}}';
    }
}
