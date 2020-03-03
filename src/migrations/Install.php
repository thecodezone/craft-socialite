<?php
/**
 * socialite plugin for Craft CMS 3.x
 *
 * Login to Craft with third-party services like Azure and Google.
 *
 * @link      https://codezone.io
 * @copyright Copyright (c) 2020 CodeZone
 */

namespace CodeZone\socialite\migrations;

use CodeZone\socialite\Socialite;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;

/**
 * @author    CodeZone
 * @package   Socialite
 * @since     0.0.0
 */
class Install extends Migration
{
    // Public Properties
    // =========================================================================

    /**
     * @var string The database driver to use
     */
    public $driver;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();
            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
            $this->insertDefaultData();
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @return bool
     */
    protected function createTables()
    {
        $tablesCreated = false;

        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%socialite_sso_accounts}}');
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                '{{%socialite_sso_accounts}}',
                [
                    'id' => $this->primaryKey(),
                    'uid' => $this->uid(),
                    'userId' => $this->integer()->notNull(),
                    'provider' => $this->string(255)->notNull(),
                    'token' => $this->string(2048)->notNull(),
                    'ssoId' => $this->string(255)->notNull(),
                    'refreshToken' => $this->string(2048)->notNull(),
                    'expiresAt' => $this->string()->notNull(),
                    'dateCreated' => $this->timestamp()->notNull(),
                    'dateUpdated' => $this->timestamp()->notNull()
                ]
            );
        }

        return $tablesCreated;
    }

    /**
     * @return void
     */
    protected function createIndexes()
    {
        $this->createIndex(
             $this->db->getIndexName(
                '{{%socialite_sso_accounts}}',
                'userId',
                false
             ),
            '{{%socialite_sso_accounts}}',
            'userId',
            false
        );
    }

    /**
     * @return void
     */
    protected function addForeignKeys()
    {
        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%social_login_accounts}}', 'userId'),
            '{{%social_login_accounts}}',
            'userId',
            '{{%users}}',
            'id',
            'CASCADE',
            null
        );

        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%social_login_accounts}}', 'id'),
            '{{%social_login_accounts}}', 'id', '{{%elements}}',
            'id',
            'CASCADE',
            null
        );
    }

    /**
     * Populates the DB with the default data.
     *
     * @return void
     */
    protected function insertDefaultData()
    {
    }

    /**
     * Removes the indexes needed for the Records used by the plugin
     *
     * @return void
     */
    protected function removeIndexes()
    {
    }

    /**
     * @return void
     */
    protected function removeTables()
    {
        $this->dropTableIfExists('{{%socialite_socialiterecord}}');
    }
}
