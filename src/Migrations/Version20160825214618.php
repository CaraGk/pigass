<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160825214618 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE payum_payment (id INT AUTO_INCREMENT NOT NULL, number VARCHAR(255) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, client_email VARCHAR(255) DEFAULT NULL, client_id VARCHAR(255) DEFAULT NULL, total_amount INT DEFAULT NULL, currency_code VARCHAR(255) DEFAULT NULL, details LONGTEXT NOT NULL COMMENT \'(DC2Type:json_array)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payum_token (hash VARCHAR(255) NOT NULL, details LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:object)\', after_url LONGTEXT DEFAULT NULL, target_url LONGTEXT NOT NULL, gateway_name VARCHAR(255) NOT NULL, PRIMARY KEY(hash)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, username_canonical VARCHAR(180) NOT NULL, email VARCHAR(180) NOT NULL, email_canonical VARCHAR(180) NOT NULL, enabled TINYINT(1) NOT NULL, salt VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, last_login DATETIME DEFAULT NULL, locked TINYINT(1) NOT NULL, expired TINYINT(1) NOT NULL, expires_at DATETIME DEFAULT NULL, confirmation_token VARCHAR(255) DEFAULT NULL, password_requested_at DATETIME DEFAULT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', credentials_expired TINYINT(1) NOT NULL, credentials_expire_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D64992FC23A8 (username_canonical), UNIQUE INDEX UNIQ_8D93D649A0D96FBF (email_canonical), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE member_info (id INT AUTO_INCREMENT NOT NULL, membership_id INT DEFAULT NULL, memberquestion_id INT DEFAULT NULL, value VARCHAR(255) DEFAULT NULL, INDEX IDX_37011D0B1FB354CD (membership_id), INDEX IDX_37011D0B9622182E (memberquestion_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payum_gateway (id INT AUTO_INCREMENT NOT NULL, structure_id INT DEFAULT NULL, gateway_name VARCHAR(255) NOT NULL, factory_name VARCHAR(255) NOT NULL, config LONGTEXT NOT NULL COMMENT \'(DC2Type:json_array)\', readable_name VARCHAR(100) NOT NULL, INDEX IDX_3BC0BD532534008B (structure_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE person (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, title VARCHAR(5) NOT NULL, surname VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, birthday DATE DEFAULT NULL, birthplace VARCHAR(255) DEFAULT NULL, phone VARCHAR(18) DEFAULT NULL, address LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', anonymous TINYINT(1) DEFAULT NULL, UNIQUE INDEX UNIQ_34DCD176A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE membership (id INT AUTO_INCREMENT NOT NULL, person_id INT DEFAULT NULL, method_id INT DEFAULT NULL, structure_id INT DEFAULT NULL, payment_id INT DEFAULT NULL, amount NUMERIC(2, 0) NOT NULL, payed_on DATETIME DEFAULT NULL, expired_on DATETIME NOT NULL, INDEX IDX_86FFD285217BBB47 (person_id), INDEX IDX_86FFD28519883967 (method_id), INDEX IDX_86FFD2852534008B (structure_id), UNIQUE INDEX UNIQ_86FFD2854C3A3BB (payment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE member_question (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, type SMALLINT NOT NULL, more LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', rank SMALLINT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE parameter (id INT AUTO_INCREMENT NOT NULL, structure_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, value VARCHAR(50) NOT NULL, active TINYINT(1) DEFAULT NULL, activates_at DATETIME DEFAULT NULL, expires_at DATETIME DEFAULT NULL, `label` VARCHAR(255) NOT NULL, category VARCHAR(255) NOT NULL, type SMALLINT NOT NULL, more LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', UNIQUE INDEX UNIQ_2A9791105E237E06 (name), INDEX IDX_2A9791102534008B (structure_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE structure (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, slug VARCHAR(150) NOT NULL, UNIQUE INDEX UNIQ_6F0137EA5E237E06 (name), UNIQUE INDEX UNIQ_6F0137EA989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE member_info ADD CONSTRAINT FK_37011D0B1FB354CD FOREIGN KEY (membership_id) REFERENCES membership (id)');
        $this->addSql('ALTER TABLE member_info ADD CONSTRAINT FK_37011D0B9622182E FOREIGN KEY (memberquestion_id) REFERENCES member_question (id)');
        $this->addSql('ALTER TABLE payum_gateway ADD CONSTRAINT FK_3BC0BD532534008B FOREIGN KEY (structure_id) REFERENCES structure (id)');
        $this->addSql('ALTER TABLE person ADD CONSTRAINT FK_34DCD176A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE membership ADD CONSTRAINT FK_86FFD285217BBB47 FOREIGN KEY (person_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE membership ADD CONSTRAINT FK_86FFD28519883967 FOREIGN KEY (method_id) REFERENCES payum_gateway (id)');
        $this->addSql('ALTER TABLE membership ADD CONSTRAINT FK_86FFD2852534008B FOREIGN KEY (structure_id) REFERENCES structure (id)');
        $this->addSql('ALTER TABLE membership ADD CONSTRAINT FK_86FFD2854C3A3BB FOREIGN KEY (payment_id) REFERENCES payum_payment (id)');
        $this->addSql('ALTER TABLE parameter ADD CONSTRAINT FK_2A9791102534008B FOREIGN KEY (structure_id) REFERENCES structure (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE membership DROP FOREIGN KEY FK_86FFD2854C3A3BB');
        $this->addSql('ALTER TABLE person DROP FOREIGN KEY FK_34DCD176A76ED395');
        $this->addSql('ALTER TABLE membership DROP FOREIGN KEY FK_86FFD28519883967');
        $this->addSql('ALTER TABLE membership DROP FOREIGN KEY FK_86FFD285217BBB47');
        $this->addSql('ALTER TABLE member_info DROP FOREIGN KEY FK_37011D0B1FB354CD');
        $this->addSql('ALTER TABLE member_info DROP FOREIGN KEY FK_37011D0B9622182E');
        $this->addSql('ALTER TABLE payum_gateway DROP FOREIGN KEY FK_3BC0BD532534008B');
        $this->addSql('ALTER TABLE membership DROP FOREIGN KEY FK_86FFD2852534008B');
        $this->addSql('ALTER TABLE parameter DROP FOREIGN KEY FK_2A9791102534008B');
        $this->addSql('DROP TABLE payum_payment');
        $this->addSql('DROP TABLE payum_token');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE member_info');
        $this->addSql('DROP TABLE payum_gateway');
        $this->addSql('DROP TABLE person');
        $this->addSql('DROP TABLE membership');
        $this->addSql('DROP TABLE member_question');
        $this->addSql('DROP TABLE parameter');
        $this->addSql('DROP TABLE structure');
    }
}
