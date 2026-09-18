<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260906160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add photo column to user table and copy existing photos from candidate_profile';
    }

    public function up(Schema $schema): void
    {
        $platform = strtolower(get_class($this->connection->getDatabasePlatform()));
        if (str_contains($platform, 'postgresql') || str_contains($platform, 'postgres')) {
            $this->addSql('ALTER TABLE "user" ADD photo VARCHAR(255) DEFAULT NULL');
            $this->addSql('UPDATE "user" u SET photo = cp.photo FROM candidate_profile cp WHERE cp.user_id = u.id AND cp.photo IS NOT NULL');
        } else {
            $this->addSql('ALTER TABLE `user` ADD photo VARCHAR(255) DEFAULT NULL');
            $this->addSql('UPDATE `user` u INNER JOIN candidate_profile cp ON cp.user_id = u.id SET u.photo = cp.photo WHERE cp.photo IS NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $platform = strtolower(get_class($this->connection->getDatabasePlatform()));
        if (str_contains($platform, 'postgresql') || str_contains($platform, 'postgres')) {
            $this->addSql('ALTER TABLE "user" DROP photo');
        } else {
            $this->addSql('ALTER TABLE `user` DROP photo');
        }
    }
}
