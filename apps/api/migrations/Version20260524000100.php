<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260524000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create initial 3NF identity/auth schema slice: roles, users, auth_tokens, login_attempts.';
    }

    public function up(Schema $schema): void
    {
        $roles = $schema->createTable('roles');
        $roles->addColumn('id', 'bigint', ['autoincrement' => true]);
        $roles->addColumn('code', 'string', ['length' => 32]);
        $roles->addColumn('name', 'string', ['length' => 64]);
        $roles->setPrimaryKey(['id']);
        $roles->addUniqueIndex(['code'], 'uniq_roles_code');
        $roles->addUniqueIndex(['name'], 'uniq_roles_name');

        $users = $schema->createTable('users');
        $users->addColumn('id', 'bigint', ['autoincrement' => true]);
        $users->addColumn('public_id', 'string', ['length' => 48]);
        $users->addColumn('email', 'string', ['length' => 255]);
        $users->addColumn('password_hash', 'string', ['length' => 255]);
        $users->addColumn('role_id', 'bigint');
        $users->addColumn('is_active', 'boolean', ['default' => true]);
        $users->addColumn('created_at', 'datetime_immutable');
        $users->addColumn('updated_at', 'datetime_immutable');
        $users->setPrimaryKey(['id']);
        $users->addUniqueIndex(['public_id'], 'uniq_users_public_id');
        $users->addUniqueIndex(['email'], 'uniq_users_email');
        $users->addIndex(['role_id'], 'idx_users_role_id');
        $users->addForeignKeyConstraint('roles', ['role_id'], ['id']);

        $authTokens = $schema->createTable('auth_tokens');
        $authTokens->addColumn('id', 'bigint', ['autoincrement' => true]);
        $authTokens->addColumn('user_id', 'bigint');
        $authTokens->addColumn('token_hash', 'string', ['length' => 64, 'fixed' => true]);
        $authTokens->addColumn('expires_at', 'datetime_immutable');
        $authTokens->addColumn('created_at', 'datetime_immutable');
        $authTokens->setPrimaryKey(['id']);
        $authTokens->addUniqueIndex(['token_hash'], 'uniq_auth_tokens_token_hash');
        $authTokens->addIndex(['user_id', 'expires_at'], 'idx_auth_tokens_user_expires');
        $authTokens->addForeignKeyConstraint('users', ['user_id'], ['id'], ['onDelete' => 'CASCADE']);

        $attempts = $schema->createTable('login_attempts');
        $attempts->addColumn('id', 'bigint', ['autoincrement' => true]);
        $attempts->addColumn('email', 'string', ['length' => 255]);
        $attempts->addColumn('ip_address', 'string', ['length' => 45]);
        $attempts->addColumn('attempted_at', 'datetime_immutable');
        $attempts->addColumn('was_successful', 'boolean');
        $attempts->setPrimaryKey(['id']);
        $attempts->addIndex(['email', 'attempted_at'], 'idx_login_attempts_email_attempted_at');
        $attempts->addIndex(['ip_address', 'attempted_at'], 'idx_login_attempts_ip_attempted_at');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('auth_tokens');
        $schema->dropTable('login_attempts');
        $schema->dropTable('users');
        $schema->dropTable('roles');
    }
}
