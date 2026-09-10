<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260910053205 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE attribute (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, type VARCHAR(50) NOT NULL, options JSON DEFAULT NULL, category_id INT NOT NULL, INDEX IDX_FA7AEFFB12469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE attribute_category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE candidate_attribute_value (id INT AUTO_INCREMENT NOT NULL, value JSON DEFAULT NULL, version INT DEFAULT 1 NOT NULL, candidate_id INT NOT NULL, attribute_id INT NOT NULL, INDEX IDX_540006F791BD8781 (candidate_id), INDEX IDX_540006F7B6E62EFA (attribute_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE candidate_profile (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, location VARCHAR(255) DEFAULT NULL, version INT DEFAULT 1 NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_E8607AEA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE cv (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, version INT DEFAULT 1 NOT NULL, candidate_id INT NOT NULL, position_id INT NOT NULL, INDEX IDX_B66FFE9291BD8781 (candidate_id), INDEX IDX_B66FFE92DD842E46 (position_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE cv_like (id INT AUTO_INCREMENT NOT NULL, cv_id INT NOT NULL, recruiter_id INT NOT NULL, INDEX IDX_CD2DA06FCFE419E2 (cv_id), INDEX IDX_CD2DA06F156BE243 (recruiter_id), UNIQUE INDEX cv_recruiter_unique (cv_id, recruiter_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE discussion_post (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, create_at DATETIME NOT NULL, position_id INT NOT NULL, author_id INT NOT NULL, INDEX IDX_7FE4C0BBDD842E46 (position_id), INDEX IDX_7FE4C0BBF675F31B (author_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE permission (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_E04992AA989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE position (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, short_description LONGTEXT NOT NULL, is_public TINYINT NOT NULL, max_project INT NOT NULL, project_tags JSON DEFAULT NULL, level VARCHAR(100) DEFAULT NULL, company VARCHAR(255) DEFAULT NULL, version INT DEFAULT 1 NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE position_attribute (position_id INT NOT NULL, attribute_id INT NOT NULL, INDEX IDX_AF5BEE86DD842E46 (position_id), INDEX IDX_AF5BEE86B6E62EFA (attribute_id), PRIMARY KEY (position_id, attribute_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE position_access_rule (id INT AUTO_INCREMENT NOT NULL, operator VARCHAR(10) NOT NULL, value JSON NOT NULL, position_id INT NOT NULL, attribute_id INT NOT NULL, INDEX IDX_5D97A8C6DD842E46 (position_id), INDEX IDX_5D97A8C6B6E62EFA (attribute_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE role (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_57698A6A989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE role_permission (role_id INT NOT NULL, permission_id INT NOT NULL, INDEX IDX_6F7DF886D60322AC (role_id), INDEX IDX_6F7DF886FED90CCA (permission_id), PRIMARY KEY (role_id, permission_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, is_verified TINYINT NOT NULL, is_blocked TINYINT NOT NULL, photo LONGTEXT DEFAULT NULL, google_id VARCHAR(255) DEFAULT NULL, facebook_id VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE attribute ADD CONSTRAINT FK_FA7AEFFB12469DE2 FOREIGN KEY (category_id) REFERENCES attribute_category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE candidate_attribute_value ADD CONSTRAINT FK_540006F791BD8781 FOREIGN KEY (candidate_id) REFERENCES candidate_profile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE candidate_attribute_value ADD CONSTRAINT FK_540006F7B6E62EFA FOREIGN KEY (attribute_id) REFERENCES attribute (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE candidate_profile ADD CONSTRAINT FK_E8607AEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cv ADD CONSTRAINT FK_B66FFE9291BD8781 FOREIGN KEY (candidate_id) REFERENCES candidate_profile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cv ADD CONSTRAINT FK_B66FFE92DD842E46 FOREIGN KEY (position_id) REFERENCES position (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cv_like ADD CONSTRAINT FK_CD2DA06FCFE419E2 FOREIGN KEY (cv_id) REFERENCES cv (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cv_like ADD CONSTRAINT FK_CD2DA06F156BE243 FOREIGN KEY (recruiter_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE discussion_post ADD CONSTRAINT FK_7FE4C0BBDD842E46 FOREIGN KEY (position_id) REFERENCES position (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE discussion_post ADD CONSTRAINT FK_7FE4C0BBF675F31B FOREIGN KEY (author_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE position_attribute ADD CONSTRAINT FK_AF5BEE86DD842E46 FOREIGN KEY (position_id) REFERENCES position (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE position_attribute ADD CONSTRAINT FK_AF5BEE86B6E62EFA FOREIGN KEY (attribute_id) REFERENCES attribute (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE position_access_rule ADD CONSTRAINT FK_5D97A8C6DD842E46 FOREIGN KEY (position_id) REFERENCES position (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE position_access_rule ADD CONSTRAINT FK_5D97A8C6B6E62EFA FOREIGN KEY (attribute_id) REFERENCES attribute (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE role_permission ADD CONSTRAINT FK_6F7DF886D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE role_permission ADD CONSTRAINT FK_6F7DF886FED90CCA FOREIGN KEY (permission_id) REFERENCES permission (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE attribute DROP FOREIGN KEY FK_FA7AEFFB12469DE2');
        $this->addSql('ALTER TABLE candidate_attribute_value DROP FOREIGN KEY FK_540006F791BD8781');
        $this->addSql('ALTER TABLE candidate_attribute_value DROP FOREIGN KEY FK_540006F7B6E62EFA');
        $this->addSql('ALTER TABLE candidate_profile DROP FOREIGN KEY FK_E8607AEA76ED395');
        $this->addSql('ALTER TABLE cv DROP FOREIGN KEY FK_B66FFE9291BD8781');
        $this->addSql('ALTER TABLE cv DROP FOREIGN KEY FK_B66FFE92DD842E46');
        $this->addSql('ALTER TABLE cv_like DROP FOREIGN KEY FK_CD2DA06FCFE419E2');
        $this->addSql('ALTER TABLE cv_like DROP FOREIGN KEY FK_CD2DA06F156BE243');
        $this->addSql('ALTER TABLE discussion_post DROP FOREIGN KEY FK_7FE4C0BBDD842E46');
        $this->addSql('ALTER TABLE discussion_post DROP FOREIGN KEY FK_7FE4C0BBF675F31B');
        $this->addSql('ALTER TABLE position_attribute DROP FOREIGN KEY FK_AF5BEE86DD842E46');
        $this->addSql('ALTER TABLE position_attribute DROP FOREIGN KEY FK_AF5BEE86B6E62EFA');
        $this->addSql('ALTER TABLE position_access_rule DROP FOREIGN KEY FK_5D97A8C6DD842E46');
        $this->addSql('ALTER TABLE position_access_rule DROP FOREIGN KEY FK_5D97A8C6B6E62EFA');
        $this->addSql('ALTER TABLE role_permission DROP FOREIGN KEY FK_6F7DF886D60322AC');
        $this->addSql('ALTER TABLE role_permission DROP FOREIGN KEY FK_6F7DF886FED90CCA');
        $this->addSql('DROP TABLE attribute');
        $this->addSql('DROP TABLE attribute_category');
        $this->addSql('DROP TABLE candidate_attribute_value');
        $this->addSql('DROP TABLE candidate_profile');
        $this->addSql('DROP TABLE cv');
        $this->addSql('DROP TABLE cv_like');
        $this->addSql('DROP TABLE discussion_post');
        $this->addSql('DROP TABLE permission');
        $this->addSql('DROP TABLE position');
        $this->addSql('DROP TABLE position_attribute');
        $this->addSql('DROP TABLE position_access_rule');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE role_permission');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
