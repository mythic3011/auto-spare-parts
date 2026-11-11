<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Transitional legacy schema; the normalized order aggregate remains a later slice. */
final class Version20261111000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Use database-generated legacy order IDs and add a unique opaque public identifier.';
    }

    public function isTransactional(): bool
    {
        return false; // MariaDB/MySQL DDL commits implicitly.
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'Legacy Orders exists only on MariaDB/MySQL; normalized schemas do not use this migration.');
        if (!$schema->hasTable('Orders')) {
            return;
        }
        $table = $schema->getTable('Orders');
        if ($table->getPrimaryKey() === null) {
            $this->addSql('ALTER TABLE Orders ADD PRIMARY KEY (orderID)');
        }
        $this->addSql('ALTER TABLE Orders MODIFY orderID INT NOT NULL AUTO_INCREMENT');
        if (!$table->hasColumn('publicID')) {
            $this->addSql('ALTER TABLE Orders ADD publicID CHAR(32) NULL, ADD UNIQUE INDEX uniq_orders_public_id (publicID)');
        }
        $this->addSql("UPDATE Orders SET publicID = REPLACE(UUID(), '-', '') WHERE publicID IS NULL");
        $this->addSql('ALTER TABLE Orders MODIFY publicID CHAR(32) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Removing public order identifiers would invalidate references.');
    }
}
