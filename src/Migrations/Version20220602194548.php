<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220602194548 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE fee CHANGE structure_id structure_id INT DEFAULT NULL, CHANGE title title VARCHAR(30) NOT NULL');
        $this->addSql('ALTER TABLE sector_rule CHANGE grade_id grade_id INT DEFAULT NULL, CHANGE sector sector INT DEFAULT NULL, CHANGE structure_id structure_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE wish CHANGE department department INT DEFAULT NULL, CHANGE structure_id structure_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE placement CHANGE person_id person_id INT DEFAULT NULL, CHANGE repartition_id repartition_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE eval_form CHANGE structure_id structure_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE grade CHANGE structure_id structure_id INT DEFAULT NULL, CHANGE is_active is_active TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE simul_period CHANGE period_id period_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE accreditation CHANGE department_id department_id INT DEFAULT NULL, CHANGE sector_id sector_id INT DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL, CHANGE structure_id structure_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payum_gateway CHANGE structure_id structure_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE sector CHANGE structure_id structure_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE eval_criteria CHANGE evalform_id evalform_id INT DEFAULT NULL, CHANGE more more VARCHAR(255) DEFAULT NULL, CHANGE required required TINYINT(1) DEFAULT NULL, CHANGE moderate moderate TINYINT(1) DEFAULT NULL, CHANGE private private TINYINT(1) DEFAULT NULL, CHANGE category category VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE evaluation CHANGE placement_id placement_id INT DEFAULT NULL, CHANGE evalcriteria_id evalcriteria_id INT DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL, CHANGE structure_id structure_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE eval_sector CHANGE sector_id sector_id INT DEFAULT NULL, CHANGE form_id form_id INT DEFAULT NULL, CHANGE structure_id structure_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE department CHANGE hospital_id hospital_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE structure CHANGE address address LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', CHANGE area area VARCHAR(100) DEFAULT NULL, CHANGE logo logo VARCHAR(255) DEFAULT NULL, CHANGE activated activated TINYINT(1) DEFAULT NULL, CHANGE fullname fullname VARCHAR(255) DEFAULT NULL, CHANGE email email VARCHAR(150) DEFAULT NULL, CHANGE areamap areamap LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', CHANGE url url VARCHAR(255) DEFAULT NULL, CHANGE phone phone VARCHAR(15) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE member_info CHANGE membership_id membership_id INT DEFAULT NULL, CHANGE memberquestion_id memberquestion_id INT DEFAULT NULL, CHANGE value value VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE partner CHANGE user_id user_id INT DEFAULT NULL, CHANGE structure_id structure_id INT DEFAULT NULL, CHANGE filters filters LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', CHANGE limits limits LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE payum_payment CHANGE number number VARCHAR(255) DEFAULT NULL, CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE client_email client_email VARCHAR(255) DEFAULT NULL, CHANGE client_id client_id VARCHAR(255) DEFAULT NULL, CHANGE total_amount total_amount INT DEFAULT NULL, CHANGE currency_code currency_code VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE salt salt VARCHAR(255) DEFAULT NULL, CHANGE last_login last_login DATETIME DEFAULT NULL, CHANGE confirmation_token confirmation_token VARCHAR(180) DEFAULT NULL, CHANGE password_requested_at password_requested_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE period CHANGE structure_id structure_id INT DEFAULT NULL, CHANGE simul_begin simul_begin DATE DEFAULT NULL, CHANGE simul_end simul_end DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE member_question CHANGE structure_id structure_id INT DEFAULT NULL, CHANGE more more LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', CHANGE required required TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE receipt CHANGE person_id person_id INT DEFAULT NULL, CHANGE structure_id structure_id INT DEFAULT NULL, CHANGE sign sign VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE hospital CHANGE structure_id structure_id INT DEFAULT NULL, CHANGE address address VARCHAR(255) DEFAULT NULL, CHANGE web web VARCHAR(255) DEFAULT NULL, CHANGE phone phone VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE parameter CHANGE structure_id structure_id INT DEFAULT NULL, CHANGE active active TINYINT(1) DEFAULT NULL, CHANGE more more LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE payum_token CHANGE details details LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:object)\'');
        $this->addSql('ALTER TABLE membership CHANGE person_id person_id INT DEFAULT NULL, CHANGE method_id method_id INT DEFAULT NULL, CHANGE structure_id structure_id INT DEFAULT NULL, CHANGE payment_id payment_id INT DEFAULT NULL, CHANGE fee_id fee_id INT DEFAULT NULL, CHANGE payed_on payed_on DATETIME DEFAULT NULL, CHANGE ref ref VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE simulation CHANGE department department INT DEFAULT NULL, CHANGE structure_id structure_id INT DEFAULT NULL, CHANGE extra extra SMALLINT DEFAULT NULL, CHANGE excess excess TINYINT(1) DEFAULT NULL, CHANGE validated validated TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE person CHANGE user_id user_id INT DEFAULT NULL, CHANGE grade_id grade_id INT DEFAULT NULL, CHANGE structure_id structure_id INT DEFAULT NULL, CHANGE birthday birthday DATE DEFAULT NULL, CHANGE birthplace birthplace VARCHAR(255) DEFAULT NULL, CHANGE phone phone VARCHAR(18) DEFAULT NULL, CHANGE address address LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', CHANGE anonymous anonymous TINYINT(1) DEFAULT NULL, CHANGE ranking ranking SMALLINT DEFAULT NULL, CHANGE graduate graduate SMALLINT DEFAULT NULL');
        $this->addSql('ALTER TABLE repartition CHANGE period_id period_id INT DEFAULT NULL, CHANGE department_id department_id INT DEFAULT NULL, CHANGE structure_id structure_id INT DEFAULT NULL, CHANGE number number SMALLINT DEFAULT NULL, CHANGE cluster cluster VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE accreditation CHANGE department_id department_id INT DEFAULT NULL, CHANGE sector_id sector_id INT DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL, CHANGE structure_id structure_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE department CHANGE hospital_id hospital_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE eval_criteria CHANGE evalform_id evalform_id INT DEFAULT NULL, CHANGE more more VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE required required TINYINT(1) DEFAULT \'NULL\', CHANGE moderate moderate TINYINT(1) DEFAULT \'NULL\', CHANGE private private TINYINT(1) DEFAULT \'NULL\', CHANGE category category VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE eval_form CHANGE structure_id structure_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE eval_sector CHANGE sector_id sector_id INT DEFAULT NULL, CHANGE form_id form_id INT DEFAULT NULL, CHANGE structure_id structure_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE evaluation CHANGE placement_id placement_id INT DEFAULT NULL, CHANGE evalcriteria_id evalcriteria_id INT DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL, CHANGE structure_id structure_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE fee CHANGE structure_id structure_id INT DEFAULT NULL, CHANGE title title VARCHAR(20) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`');
        $this->addSql('ALTER TABLE grade CHANGE structure_id structure_id INT DEFAULT NULL, CHANGE is_active is_active TINYINT(1) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE hospital CHANGE structure_id structure_id INT DEFAULT NULL, CHANGE address address VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE web web VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE phone phone VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE member_info CHANGE membership_id membership_id INT DEFAULT NULL, CHANGE memberquestion_id memberquestion_id INT DEFAULT NULL, CHANGE value value VARCHAR(255) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_unicode_ci`');
        $this->addSql('ALTER TABLE member_question CHANGE structure_id structure_id INT DEFAULT NULL, CHANGE more more LONGTEXT CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_unicode_ci` COMMENT \'(DC2Type:array)\', CHANGE required required TINYINT(1) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE membership CHANGE person_id person_id INT DEFAULT NULL, CHANGE fee_id fee_id INT DEFAULT NULL, CHANGE method_id method_id INT DEFAULT NULL, CHANGE structure_id structure_id INT DEFAULT NULL, CHANGE payment_id payment_id INT DEFAULT NULL, CHANGE payed_on payed_on DATETIME DEFAULT \'NULL\', CHANGE ref ref VARCHAR(50) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_unicode_ci`');
        $this->addSql('ALTER TABLE parameter CHANGE structure_id structure_id INT DEFAULT NULL, CHANGE more more LONGTEXT CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_unicode_ci` COMMENT \'(DC2Type:array)\', CHANGE active active TINYINT(1) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE partner CHANGE user_id user_id INT DEFAULT NULL, CHANGE structure_id structure_id INT DEFAULT NULL, CHANGE filters filters LONGTEXT CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:array)\', CHANGE limits limits LONGTEXT CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE payum_gateway CHANGE structure_id structure_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payum_payment CHANGE number number VARCHAR(255) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_unicode_ci`, CHANGE description description VARCHAR(255) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_unicode_ci`, CHANGE client_email client_email VARCHAR(255) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_unicode_ci`, CHANGE client_id client_id VARCHAR(255) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_unicode_ci`, CHANGE total_amount total_amount INT DEFAULT NULL, CHANGE currency_code currency_code VARCHAR(255) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_unicode_ci`');
        $this->addSql('ALTER TABLE payum_token CHANGE details details LONGTEXT CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_unicode_ci` COMMENT \'(DC2Type:object)\'');
        $this->addSql('ALTER TABLE period CHANGE structure_id structure_id INT DEFAULT NULL, CHANGE simul_begin simul_begin DATE DEFAULT \'NULL\', CHANGE simul_end simul_end DATE DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE person CHANGE user_id user_id INT DEFAULT NULL, CHANGE grade_id grade_id INT DEFAULT NULL, CHANGE structure_id structure_id INT DEFAULT NULL, CHANGE birthday birthday DATE DEFAULT \'NULL\', CHANGE birthplace birthplace VARCHAR(255) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_unicode_ci`, CHANGE phone phone VARCHAR(18) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_unicode_ci`, CHANGE address address LONGTEXT CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_unicode_ci` COMMENT \'(DC2Type:array)\', CHANGE anonymous anonymous TINYINT(1) DEFAULT \'NULL\', CHANGE ranking ranking SMALLINT DEFAULT NULL, CHANGE graduate graduate SMALLINT DEFAULT NULL');
        $this->addSql('ALTER TABLE placement CHANGE person_id person_id INT DEFAULT NULL, CHANGE repartition_id repartition_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE receipt CHANGE person_id person_id INT DEFAULT NULL, CHANGE structure_id structure_id INT DEFAULT NULL, CHANGE sign sign VARCHAR(255) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_unicode_ci`');
        $this->addSql('ALTER TABLE repartition CHANGE period_id period_id INT DEFAULT NULL, CHANGE department_id department_id INT DEFAULT NULL, CHANGE structure_id structure_id INT DEFAULT NULL, CHANGE number number SMALLINT DEFAULT NULL, CHANGE cluster cluster VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE sector CHANGE structure_id structure_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE sector_rule CHANGE grade_id grade_id INT DEFAULT NULL, CHANGE sector sector INT DEFAULT NULL, CHANGE structure_id structure_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE simul_period CHANGE period_id period_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE simulation CHANGE department department INT DEFAULT NULL, CHANGE structure_id structure_id INT DEFAULT NULL, CHANGE extra extra SMALLINT DEFAULT NULL, CHANGE excess excess TINYINT(1) DEFAULT \'NULL\', CHANGE validated validated TINYINT(1) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE structure CHANGE fullname fullname VARCHAR(255) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_unicode_ci`, CHANGE email email VARCHAR(150) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_unicode_ci`, CHANGE address address LONGTEXT CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_unicode_ci` COMMENT \'(DC2Type:array)\', CHANGE url url VARCHAR(255) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_unicode_ci`, CHANGE phone phone VARCHAR(15) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_unicode_ci`, CHANGE area area VARCHAR(100) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_unicode_ci`, CHANGE areamap areamap LONGTEXT CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_unicode_ci` COMMENT \'(DC2Type:array)\', CHANGE logo logo VARCHAR(255) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_unicode_ci`, CHANGE activated activated TINYINT(1) DEFAULT \'NULL\', CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE user CHANGE salt salt VARCHAR(255) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_unicode_ci`, CHANGE last_login last_login DATETIME DEFAULT \'NULL\', CHANGE confirmation_token confirmation_token VARCHAR(180) CHARACTER SET utf8 DEFAULT \'NULL\' COLLATE `utf8_unicode_ci`, CHANGE password_requested_at password_requested_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE wish CHANGE department department INT DEFAULT NULL, CHANGE structure_id structure_id INT DEFAULT NULL');
    }
}
