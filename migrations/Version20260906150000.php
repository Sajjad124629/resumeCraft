<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260906150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_blocked to user table and project_tags to position table';
    }

    public function up(Schema $schema): void
    {
        $platform = strtolower(get_class($this->connection->getDatabasePlatform()));
        if (str_contains($platform, 'postgresql') || str_contains($platform, 'postgres')) {
            $this->addSql('ALTER TABLE "user" ADD is_blocked BOOLEAN DEFAULT false NOT NULL');
            $this->addSql('ALTER TABLE position ADD project_tags JSON DEFAULT NULL');
        } else {
            $this->addSql('ALTER TABLE `user` ADD is_blocked TINYINT(1) DEFAULT 0 NOT NULL');
            $this->addSql('ALTER TABLE position ADD project_tags JSON DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $platform = strtolower(get_class($this->connection->getDatabasePlatform()));
        if (str_contains($platform, 'postgresql') || str_contains($platform, 'postgres')) {
            $this->addSql('ALTER TABLE "user" DROP is_blocked');
            $this->addSql('ALTER TABLE position DROP project_tags');
        } else {
            $this->addSql('ALTER TABLE `user` DROP is_blocked');
            $this->addSql('ALTER TABLE position DROP project_tags');
        }
    }
}
