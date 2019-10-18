<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190811173211 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE repartition (id INT AUTO_INCREMENT NOT NULL, period_id INT DEFAULT NULL, department_id INT DEFAULT NULL, structure_id INT DEFAULT NULL, number SMALLINT DEFAULT NULL, cluster VARCHAR(100) DEFAULT NULL, INDEX IDX_82B791A0EC8B7ADE (period_id), INDEX IDX_82B791A0AE80F5DF (department_id), INDEX IDX_82B791A02534008B (structure_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE period (id INT AUTO_INCREMENT NOT NULL, structure_id INT DEFAULT NULL, name VARCHAR(100) NOT NULL, begin DATE NOT NULL, end DATE NOT NULL, INDEX IDX_C5B81ECE2534008B (structure_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wish (id INT AUTO_INCREMENT NOT NULL, department INT DEFAULT NULL, sim_id INT NOT NULL, structure_id INT DEFAULT NULL, rank INT NOT NULL, INDEX IDX_D7D174C9CD1DE18A (department), INDEX IDX_D7D174C9F81AF80C (sim_id), INDEX IDX_D7D174C92534008B (structure_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE simul_period (id INT AUTO_INCREMENT NOT NULL, period_id INT DEFAULT NULL, begin DATE NOT NULL, end DATE NOT NULL, UNIQUE INDEX UNIQ_BB16A74BEC8B7ADE (period_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE placement (id INT AUTO_INCREMENT NOT NULL, person_id INT DEFAULT NULL, repartition_id INT DEFAULT NULL, INDEX IDX_48DB750E217BBB47 (person_id), INDEX IDX_48DB750E826605A6 (repartition_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE department (id INT AUTO_INCREMENT NOT NULL, hospital_id INT DEFAULT NULL, nameOB VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, INDEX IDX_CD1DE18A63DBB69 (hospital_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE grade (id INT AUTO_INCREMENT NOT NULL, structure_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, rank INT NOT NULL, is_active TINYINT(1) DEFAULT NULL, INDEX IDX_595AAE342534008B (structure_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE eval_criteria (id INT AUTO_INCREMENT NOT NULL, evalform_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, type SMALLINT NOT NULL, more VARCHAR(255) DEFAULT NULL, rank SMALLINT NOT NULL, required TINYINT(1) DEFAULT NULL, moderate TINYINT(1) DEFAULT NULL, private TINYINT(1) DEFAULT NULL, category VARCHAR(255) DEFAULT NULL, INDEX IDX_F3886C92BF951A59 (evalform_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sector (id INT AUTO_INCREMENT NOT NULL, structure_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, is_default TINYINT(1) NOT NULL, INDEX IDX_4BA3D9E82534008B (structure_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE simulation (id INT NOT NULL, person_id INT NOT NULL, department INT DEFAULT NULL, structure_id INT DEFAULT NULL, rank INT NOT NULL, extra SMALLINT DEFAULT NULL, active TINYINT(1) NOT NULL, excess TINYINT(1) DEFAULT NULL, validated TINYINT(1) DEFAULT NULL, INDEX IDX_CBDA467B217BBB47 (person_id), INDEX IDX_CBDA467BCD1DE18A (department), INDEX IDX_CBDA467B2534008B (structure_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE accreditation (id INT AUTO_INCREMENT NOT NULL, department_id INT DEFAULT NULL, sector_id INT DEFAULT NULL, user_id INT DEFAULT NULL, structure_id INT DEFAULT NULL, supervisor VARCHAR(100) NOT NULL, begin DATE NOT NULL, end DATE NOT NULL, comment LONGTEXT DEFAULT NULL, INDEX IDX_3BF9D0D8AE80F5DF (department_id), INDEX IDX_3BF9D0D8DE95C867 (sector_id), INDEX IDX_3BF9D0D8A76ED395 (user_id), INDEX IDX_3BF9D0D82534008B (structure_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sector_rule (id INT AUTO_INCREMENT NOT NULL, grade_id INT DEFAULT NULL, sector INT DEFAULT NULL, structure_id INT DEFAULT NULL, relation VARCHAR(10) NOT NULL, INDEX IDX_90B6D32DFE19A1A8 (grade_id), INDEX IDX_90B6D32D4BA3D9E8 (sector), INDEX IDX_90B6D32D2534008B (structure_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE evaluation (id INT AUTO_INCREMENT NOT NULL, placement_id INT DEFAULT NULL, evalcriteria_id INT DEFAULT NULL, user_id INT DEFAULT NULL, structure_id INT DEFAULT NULL, value TEXT NOT NULL, created_ad DATETIME NOT NULL, validated TINYINT(1) NOT NULL, moderated TINYINT(1) NOT NULL, INDEX IDX_1323A5752F966E9D (placement_id), INDEX IDX_1323A575B87679D7 (evalcriteria_id), INDEX IDX_1323A575A76ED395 (user_id), INDEX IDX_1323A5752534008B (structure_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE hospital (id INT AUTO_INCREMENT NOT NULL, structure_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, address VARCHAR(255) DEFAULT NULL, web VARCHAR(255) DEFAULT NULL, phone VARCHAR(20) DEFAULT NULL, description LONGTEXT DEFAULT NULL, INDEX IDX_4282C85B2534008B (structure_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE eval_form (id INT AUTO_INCREMENT NOT NULL, structure_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_1BABA6B2534008B (structure_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE partner (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, structure_id INT DEFAULT NULL, name VARCHAR(100) NOT NULL, filters LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', limits LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', UNIQUE INDEX UNIQ_312B3E16A76ED395 (user_id), INDEX IDX_312B3E162534008B (structure_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE eval_sector (id INT AUTO_INCREMENT NOT NULL, sector_id INT DEFAULT NULL, form_id INT DEFAULT NULL, structure_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_52F3BF4FDE95C867 (sector_id), INDEX IDX_52F3BF4F5FF69B7D (form_id), INDEX IDX_52F3BF4F2534008B (structure_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE repartition ADD CONSTRAINT FK_82B791A0EC8B7ADE FOREIGN KEY (period_id) REFERENCES period (id)');
        $this->addSql('ALTER TABLE repartition ADD CONSTRAINT FK_82B791A0AE80F5DF FOREIGN KEY (department_id) REFERENCES department (id)');
        $this->addSql('ALTER TABLE repartition ADD CONSTRAINT FK_82B791A02534008B FOREIGN KEY (structure_id) REFERENCES structure (id)');
        $this->addSql('ALTER TABLE period ADD CONSTRAINT FK_C5B81ECE2534008B FOREIGN KEY (structure_id) REFERENCES structure (id)');
        $this->addSql('ALTER TABLE wish ADD CONSTRAINT FK_D7D174C9CD1DE18A FOREIGN KEY (department) REFERENCES department (id)');
        $this->addSql('ALTER TABLE wish ADD CONSTRAINT FK_D7D174C9F81AF80C FOREIGN KEY (sim_id) REFERENCES simulation (id)');
        $this->addSql('ALTER TABLE wish ADD CONSTRAINT FK_D7D174C92534008B FOREIGN KEY (structure_id) REFERENCES structure (id)');
        $this->addSql('ALTER TABLE simul_period ADD CONSTRAINT FK_BB16A74BEC8B7ADE FOREIGN KEY (period_id) REFERENCES period (id)');
        $this->addSql('ALTER TABLE placement ADD CONSTRAINT FK_48DB750E217BBB47 FOREIGN KEY (person_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE placement ADD CONSTRAINT FK_48DB750E826605A6 FOREIGN KEY (repartition_id) REFERENCES repartition (id)');
        $this->addSql('ALTER TABLE department ADD CONSTRAINT FK_CD1DE18A63DBB69 FOREIGN KEY (hospital_id) REFERENCES hospital (id)');
        $this->addSql('ALTER TABLE grade ADD CONSTRAINT FK_595AAE342534008B FOREIGN KEY (structure_id) REFERENCES structure (id)');
        $this->addSql('ALTER TABLE eval_criteria ADD CONSTRAINT FK_F3886C92BF951A59 FOREIGN KEY (evalform_id) REFERENCES eval_form (id)');
        $this->addSql('ALTER TABLE sector ADD CONSTRAINT FK_4BA3D9E82534008B FOREIGN KEY (structure_id) REFERENCES structure (id)');
        $this->addSql('ALTER TABLE simulation ADD CONSTRAINT FK_CBDA467B217BBB47 FOREIGN KEY (person_id) REFERENCES person (id)');
        $this->addSql('ALTER TABLE simulation ADD CONSTRAINT FK_CBDA467BCD1DE18A FOREIGN KEY (department) REFERENCES department (id)');
        $this->addSql('ALTER TABLE simulation ADD CONSTRAINT FK_CBDA467B2534008B FOREIGN KEY (structure_id) REFERENCES structure (id)');
        $this->addSql('ALTER TABLE accreditation ADD CONSTRAINT FK_3BF9D0D8AE80F5DF FOREIGN KEY (department_id) REFERENCES department (id)');
        $this->addSql('ALTER TABLE accreditation ADD CONSTRAINT FK_3BF9D0D8DE95C867 FOREIGN KEY (sector_id) REFERENCES sector (id)');
        $this->addSql('ALTER TABLE accreditation ADD CONSTRAINT FK_3BF9D0D8A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE accreditation ADD CONSTRAINT FK_3BF9D0D82534008B FOREIGN KEY (structure_id) REFERENCES structure (id)');
        $this->addSql('ALTER TABLE sector_rule ADD CONSTRAINT FK_90B6D32DFE19A1A8 FOREIGN KEY (grade_id) REFERENCES grade (id)');
        $this->addSql('ALTER TABLE sector_rule ADD CONSTRAINT FK_90B6D32D4BA3D9E8 FOREIGN KEY (sector) REFERENCES sector (id)');
        $this->addSql('ALTER TABLE sector_rule ADD CONSTRAINT FK_90B6D32D2534008B FOREIGN KEY (structure_id) REFERENCES structure (id)');
        $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_1323A5752F966E9D FOREIGN KEY (placement_id) REFERENCES placement (id)');
        $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_1323A575B87679D7 FOREIGN KEY (evalcriteria_id) REFERENCES eval_criteria (id)');
        $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_1323A575A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_1323A5752534008B FOREIGN KEY (structure_id) REFERENCES structure (id)');
        $this->addSql('ALTER TABLE hospital ADD CONSTRAINT FK_4282C85B2534008B FOREIGN KEY (structure_id) REFERENCES structure (id)');
        $this->addSql('ALTER TABLE eval_form ADD CONSTRAINT FK_1BABA6B2534008B FOREIGN KEY (structure_id) REFERENCES structure (id)');
        $this->addSql('ALTER TABLE partner ADD CONSTRAINT FK_312B3E16A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE partner ADD CONSTRAINT FK_312B3E162534008B FOREIGN KEY (structure_id) REFERENCES structure (id)');
        $this->addSql('ALTER TABLE eval_sector ADD CONSTRAINT FK_52F3BF4FDE95C867 FOREIGN KEY (sector_id) REFERENCES sector (id)');
        $this->addSql('ALTER TABLE eval_sector ADD CONSTRAINT FK_52F3BF4F5FF69B7D FOREIGN KEY (form_id) REFERENCES eval_form (id)');
        $this->addSql('ALTER TABLE eval_sector ADD CONSTRAINT FK_52F3BF4F2534008B FOREIGN KEY (structure_id) REFERENCES structure (id)');
        $this->addSql('ALTER TABLE structure CHANGE address address LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE person ADD grade_id INT DEFAULT NULL, ADD structure_id INT DEFAULT NULL, ADD ranking SMALLINT DEFAULT NULL, ADD graduate SMALLINT DEFAULT NULL');
        $this->addSql('ALTER TABLE person ADD CONSTRAINT FK_34DCD176FE19A1A8 FOREIGN KEY (grade_id) REFERENCES grade (id)');
        $this->addSql('ALTER TABLE person ADD CONSTRAINT FK_34DCD1762534008B FOREIGN KEY (structure_id) REFERENCES structure (id)');
        $this->addSql('CREATE INDEX IDX_34DCD176FE19A1A8 ON person (grade_id)');
        $this->addSql('CREATE INDEX IDX_34DCD1762534008B ON person (structure_id)');
        $this->addSql('ALTER TABLE parameter ADD activates_at DATETIME DEFAULT NULL, ADD expires_at DATETIME DEFAULT NULL');
    }

    public function postUp(Schema $schema)
    {
        $this->connection->exec('UPDATE fee SET is_counted = true WHERE 1');
        $this->connection->exec("INSERT INTO parameter (name, value, active, label, category, type) VALUES ('general_title', 'Site d\'exemple', 1, 'Nom du site', 'General', 1)");
        $this->connection->exec("INSERT INTO parameter (name, value, active, label, category, type, more) VALUES ('general_show', 'logo', 1, 'Afficher le logo ou le titre en entête ?', 'General', 3, 'a:4:{s:4:\"none\";s:5:\"Aucun\";s:4:\"logo\";s:4:\"Logo\";s:5:\"title\";s:5:\"Titre\";s:4:\"both\";s:8:\"Les deux\";}')");
        $this->connection->exec("INSERT INTO parameter (name, value, active, label, category, type) VALUES ('general_color', '#000000', 1, 'Couleur de fond en entête ?', 'General', 1)");
    }

    public function preDown(Schema $schema)
    {
        $this->connection->exec("DELETE FROM parameter WHERE name='general_title'");
        $this->connection->exec("DELETE FROM parameter WHERE name='general_show'");
        $this->connection->exec("DELETE FROM parameter WHERE name='general_color'");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE placement DROP FOREIGN KEY FK_48DB750E826605A6');
        $this->addSql('ALTER TABLE repartition DROP FOREIGN KEY FK_82B791A0EC8B7ADE');
        $this->addSql('ALTER TABLE simul_period DROP FOREIGN KEY FK_BB16A74BEC8B7ADE');
        $this->addSql('ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A5752F966E9D');
        $this->addSql('ALTER TABLE repartition DROP FOREIGN KEY FK_82B791A0AE80F5DF');
        $this->addSql('ALTER TABLE wish DROP FOREIGN KEY FK_D7D174C9CD1DE18A');
        $this->addSql('ALTER TABLE simulation DROP FOREIGN KEY FK_CBDA467BCD1DE18A');
        $this->addSql('ALTER TABLE accreditation DROP FOREIGN KEY FK_3BF9D0D8AE80F5DF');
        $this->addSql('ALTER TABLE sector_rule DROP FOREIGN KEY FK_90B6D32DFE19A1A8');
        $this->addSql('ALTER TABLE person DROP FOREIGN KEY FK_34DCD176FE19A1A8');
        $this->addSql('ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A575B87679D7');
        $this->addSql('ALTER TABLE accreditation DROP FOREIGN KEY FK_3BF9D0D8DE95C867');
        $this->addSql('ALTER TABLE sector_rule DROP FOREIGN KEY FK_90B6D32D4BA3D9E8');
        $this->addSql('ALTER TABLE eval_sector DROP FOREIGN KEY FK_52F3BF4FDE95C867');
        $this->addSql('ALTER TABLE wish DROP FOREIGN KEY FK_D7D174C9F81AF80C');
        $this->addSql('ALTER TABLE department DROP FOREIGN KEY FK_CD1DE18A63DBB69');
        $this->addSql('ALTER TABLE eval_criteria DROP FOREIGN KEY FK_F3886C92BF951A59');
        $this->addSql('ALTER TABLE eval_sector DROP FOREIGN KEY FK_52F3BF4F5FF69B7D');
        $this->addSql('DROP TABLE repartition');
        $this->addSql('DROP TABLE period');
        $this->addSql('DROP TABLE wish');
        $this->addSql('DROP TABLE simul_period');
        $this->addSql('DROP TABLE placement');
        $this->addSql('DROP TABLE department');
        $this->addSql('DROP TABLE grade');
        $this->addSql('DROP TABLE eval_criteria');
        $this->addSql('DROP TABLE sector');
        $this->addSql('DROP TABLE simulation');
        $this->addSql('DROP TABLE accreditation');
        $this->addSql('DROP TABLE sector_rule');
        $this->addSql('DROP TABLE evaluation');
        $this->addSql('DROP TABLE hospital');
        $this->addSql('DROP TABLE eval_form');
        $this->addSql('DROP TABLE partner');
        $this->addSql('DROP TABLE eval_sector');
        $this->addSql('ALTER TABLE person DROP FOREIGN KEY FK_34DCD1762534008B');
        $this->addSql('DROP INDEX IDX_34DCD176FE19A1A8 ON person');
        $this->addSql('DROP INDEX IDX_34DCD1762534008B ON person');
        $this->addSql('ALTER TABLE person DROP grade_id, DROP structure_id, DROP ranking, DROP graduate');
        $this->addSql('ALTER TABLE structure CHANGE address address LONGTEXT NOT NULL COLLATE utf8_unicode_ci COMMENT \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE parameter DROP activates_at, DROP expires_at');
    }
}
