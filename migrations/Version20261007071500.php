<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: SupportTicket table creation
 */
final class Version20261007071500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates the support_ticket table for Power Automate and Dropbox support ticketing system';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS support_ticket (
            id INT AUTO_INCREMENT NOT NULL,
            ticket_id VARCHAR(64) NOT NULL,
            summary LONGTEXT NOT NULL,
            priority VARCHAR(20) NOT NULL,
            status VARCHAR(20) NOT NULL,
            reported_by VARCHAR(255) NOT NULL,
            reporter_email VARCHAR(255) DEFAULT NULL,
            position_title VARCHAR(255) DEFAULT NULL,
            page_link LONGTEXT DEFAULT NULL,
            file_name VARCHAR(255) DEFAULT NULL,
            cloud_path VARCHAR(500) DEFAULT NULL,
            provider VARCHAR(50) DEFAULT \'dropbox\' NOT NULL,
            is_simulated TINYINT DEFAULT 0 NOT NULL,
            admin_notes LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            solved_at DATETIME DEFAULT NULL,
            user_id INT DEFAULT NULL,
            UNIQUE INDEX UNIQ_1F5A4D53700047D2 (ticket_id),
            INDEX IDX_1F5A4D53A76ED395 (user_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Check if constraint exists before adding or add safely
        $this->addSql('ALTER TABLE support_ticket ADD CONSTRAINT FK_1F5A4D53A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE support_ticket DROP FOREIGN KEY IF EXISTS FK_1F5A4D53A76ED395');
        $this->addSql('DROP TABLE IF EXISTS support_ticket');
    }
}

