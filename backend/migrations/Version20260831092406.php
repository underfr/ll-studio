<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Schéma initial du portfolio : catégories, photos, albums, utilisateurs
 * et messages de contact, plus la table de transport Messenger.
 */
final class Version20260831092406 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Schéma initial : category, photo, album, album_photo, user, message_contact.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE album (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(120) NOT NULL, slug VARCHAR(140) NOT NULL, description LONGTEXT DEFAULT NULL, visible TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, category_id INT NOT NULL, owner_id INT DEFAULT NULL, cover_photo_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_39986E43989D9B62 (slug), INDEX IDX_39986E4312469DE2 (category_id), INDEX IDX_39986E437E3C61F9 (owner_id), INDEX IDX_39986E43A69B8AD7 (cover_photo_id), INDEX idx_album_visible_created (visible, created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE album_photo (album_id INT NOT NULL, photo_id INT NOT NULL, INDEX IDX_620FCE3E1137ABCF (album_id), INDEX IDX_620FCE3E7E9E4C8C (photo_id), PRIMARY KEY (album_id, photo_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, slug VARCHAR(60) NOT NULL, UNIQUE INDEX UNIQ_64C19C15E237E06 (name), UNIQUE INDEX UNIQ_64C19C1989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE message_contact (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, subject VARCHAR(150) NOT NULL, message LONGTEXT NOT NULL, is_read TINYINT NOT NULL, created_at DATETIME NOT NULL, INDEX idx_message_read_created (is_read, created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE photo (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(120) NOT NULL, description LONGTEXT DEFAULT NULL, alt LONGTEXT NOT NULL, file_path VARCHAR(255) NOT NULL, visible TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, category_id INT NOT NULL, owner_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_14B7841882A8E361 (file_path), INDEX IDX_14B7841812469DE2 (category_id), INDEX IDX_14B784187E3C61F9 (owner_id), INDEX idx_photo_visible_created (visible, created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(50) NOT NULL, last_name VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE album ADD CONSTRAINT FK_39986E4312469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE album ADD CONSTRAINT FK_39986E437E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE album ADD CONSTRAINT FK_39986E43A69B8AD7 FOREIGN KEY (cover_photo_id) REFERENCES photo (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE album_photo ADD CONSTRAINT FK_620FCE3E1137ABCF FOREIGN KEY (album_id) REFERENCES album (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE album_photo ADD CONSTRAINT FK_620FCE3E7E9E4C8C FOREIGN KEY (photo_id) REFERENCES photo (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE photo ADD CONSTRAINT FK_14B7841812469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE photo ADD CONSTRAINT FK_14B784187E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE album DROP FOREIGN KEY FK_39986E4312469DE2');
        $this->addSql('ALTER TABLE album DROP FOREIGN KEY FK_39986E437E3C61F9');
        $this->addSql('ALTER TABLE album DROP FOREIGN KEY FK_39986E43A69B8AD7');
        $this->addSql('ALTER TABLE album_photo DROP FOREIGN KEY FK_620FCE3E1137ABCF');
        $this->addSql('ALTER TABLE album_photo DROP FOREIGN KEY FK_620FCE3E7E9E4C8C');
        $this->addSql('ALTER TABLE photo DROP FOREIGN KEY FK_14B7841812469DE2');
        $this->addSql('ALTER TABLE photo DROP FOREIGN KEY FK_14B784187E3C61F9');
        $this->addSql('DROP TABLE album');
        $this->addSql('DROP TABLE album_photo');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE message_contact');
        $this->addSql('DROP TABLE photo');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
