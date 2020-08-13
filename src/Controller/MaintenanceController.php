<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2013-2020 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route,
    Symfony\Component\Security\Core\Security,
    Symfony\Component\Security\Core\Exception\AccessDeniedException,
    Symfony\Component\HttpFoundation\Session\SessionInterface,
    Symfony\Component\HttpFoundation\Request,
    Doctrine\ORM\EntityManagerInterface,
    FOS\UserBundle\Model\UserManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template,
    Doctrine\DBAL\Migrations\Migration,
    Doctrine\DBAL\Migrations\Configuration\Configuration;
use App\Entity\Parameter;
use App\Entity\Fee;

/**
 * Maintenance controller.
 */
class MaintenanceController extends AbstractController
{
    protected $security, $session, $em, $um;

    public function __construct(Security $security, SessionInterface $session, UserManagerInterface $um, EntityManagerInterface $em)
    {
        $this->security = $security;
        $this->em = $em;
        $this->um = $um;
        $this->session = $session;
    }

    /**
     * Database update if needed
     *
     * @Route("/update", name="core_maintenance_update")
     */
    public function updateAction()
    {
        /** Update database if some migrations are pending */
        if ($this->hasToMigrate($this->getDoctrine()->getConnection())) {
            $this->session->getFlashBag()->add('notice', 'Mise à jour de la base de donnée effectuée.');

            /** Go to first user form if repository User is empty */
            if(!$this->em->getRepository('App:User')->findAll()){
                return $this->redirect($this->generateUrl('user_person_install'));
            }
        } else {
            $this->session->getFlashBag()->add('notice', 'Toutes les mises à jour de la base de donnée ont déjà été effectuées.');
        }

        return $this->redirect($this->generateUrl('core_maintenance_correct_db_parameters'));
    }

    private function hasToMigrate($conn)
    {
        $dir = __DIR__.'/../../../../app/DoctrineMigrations';
        $configuration = new Configuration($conn);
        $configuration->setMigrationsNamespace('Application\Migrations');
        $configuration->setMigrationsTableName('migration_versions');
        $configuration->setMigrationsDirectory($dir);
        $configuration->registerMigrationsFromDirectory($dir);

        $executedMigrations = $configuration->getMigratedVersions();
        $availableMigrations = $configuration->getAvailableVersions();
        $newMigrations = count($availableMigrations) - count($executedMigrations);
        $executedUnavailableMigrations = array_diff($executedMigrations, $availableMigrations);

        if ($newMigrations > 0 and !$executedUnavailableMigrations) {
            $migration = new Migration($configuration);
            if ($migration->migrate()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Correct missing parameters
     *
     * @Route("/db/parameters", name="core_maintenance_correct_db_parameters")
     */
    public function correctDBParametersAction()
    {
        $structures = $this->em->getRepository('App:Structure')->findAll();
        $count = 0;

        foreach ($structures as $structure) {
            $slug = $structure->getSlug();
            $now = new \DateTime('now');
            $parameters = array(
                0 => array('setName' => 'reg_' . $slug . '_date', 'setValue' => $now->format('d-m-Y'), 'setActive' => true, 'setLabel' => 'Date anniversaire des adhésions', 'setCategory' => 'Module Adhesion', 'setType' => 1, 'setMore' => null, 'setStructure' => $structure),
                1 => array('setName' => 'reg_' . $slug . '_periodicity', 'setValue' => '+ 1 year', 'setActive' => true, 'setLabel' => 'Périodicité des adhésions', 'setCategory' => 'Module Adhesion', 'setType' => 3, 'setMore' => array("1 mois" => "+ 1 month", "2 mois" => "+ 2 months", "6 mois" => "+ 6 months", "1 an" => "+ 1 year", "2 ans" => "+ 2 years", "3 ans" => "+ 3 years"), 'setStructure' => $structure),
                2 => array('setName' => 'reg_' . $slug . '_payment', 'setValue' => 60, 'setActive' => true, 'setLabel' => 'Montant de la cotisation (EUR)', 'setCategory' => 'Module Adhesion', 'setType' => 1, 'setMore' => null, 'setStructure' => $structure),
                3 => array('setName' => 'reg_' . $slug . '_print', 'setValue' => false, 'setActive' => true, 'setLabel' => 'Nécessité de retourner le bulletin d\'adhésion imprimé et signé', 'setCategory' => 'Module Adhesion', 'setType' => 3, 'setMore' => array("Oui" => true, "Non" => false), 'setStructure' => $structure),
                4 => array('setName' => 'reg_' . $slug . '_anticipated', 'setValue' => '+ 3 months', 'setActive' => true, 'setLabel' => 'Adhésions anticipées de :', 'setCategory' => 'Module Adhesion', 'setType' => 3, 'setMore' => array("Pas d'adhésion anticipée" => "+ 0 day", "1 mois" => "+ 1 month", "2 mois" => "+ 2 months", "3 mois" => "+ 3 months", "4 mois" => "+ 4 months", "5 mois" => "+ 5 months", "6 mois" => "+ 6 months"), 'setStructure' => $structure),
                5 => array('setName' => 'eval_' . $slug . '_active', 'setValue' => false, 'setActive' => true, 'setLabel' => 'Activer le module d\'évaluation', 'setCategory' => 'Module Evaluation', 'setType' => 2, 'setMore' => null, 'setStructure' => $structure),
                6 => array('setName' => 'simul_' . $slug . '_active', 'setValue' => false, 'setActive' => true, 'setLabel' => 'Activer le module de simulation de choix de stage', 'setCategory' => 'Module Simulation', 'setType' => 2, 'setMore' => null, 'setStructure' => $structure),
                7 => array('setName' => 'eval_' . $slug . '_limit', 'setValue' => 3, 'setActive' => true, 'setLabel' => 'Durée de vie des évaluations (en années)', 'setCategory' => 'Module Evaluation', 'setType' => 1, 'setMore' => null, 'setStructure' => $structure),
                8 => array('setName' => 'eval_' . $slug . '_moderate', 'setValue' => true, 'setActive' => true, 'setLabel' => 'Activer la modération a priori', 'setCategory' => 'Module Evaluation', 'setType' => 2, 'setMore' => null, 'setStructure' => $structure),
                9 => array('setName' => 'eval_' . $slug . '_unevaluated', 'setValue' => true, 'setActive' => true, 'setLabel' => 'Interdire l\'accès aux évaluations si des stages sont non évalutés', 'setCategory' => 'Module Evaluation', 'setType' => 2, 'setMore' => null, 'setStructure' => $structure),
                10 => array('setName' => 'reg_' . $slug . '_active', 'setValue' => true, 'setActive' => true, 'setLabel' => 'Activer le module d\'adhésion', 'setCategory' => 'Module Adhesion', 'setType' => 2, 'setMore' => null, 'setStructure' => $structure),
                11 => array('setName' => 'reg_' . $slug . '_auto', 'setValue' => true, 'setActive' => true, 'setLabel' => 'Autoriser l\'enrgistrement direct sur le site', 'setCategory' => 'Module Adhesion', 'setType' => 2, 'setMore' => null, 'setStructure' => $structure),
                12 => array('setName' => 'eval_' . $slug . '_nonmember', 'setValue' => true, 'setActive' => true, 'setLabel' => 'Interdire l\'affichage des évaluations aux non-membres', 'setCategory' => 'Module Evaluation', 'setType' => 2, 'setMore' => null, 'setStructure' => $structure),
                13 => array('setName' => 'eval_' . $slug . '_min', 'setValue' => 3, 'setActive' => true, 'setLabel' => 'Nombre d\'évaluations minimal pour l\'affichage aux étudiants et enseignants', 'setCategory' => 'Module Evaluation', 'setType' => 1, 'setMore' => null, 'setStructure' => $structure),
                14 => array('setName' => 'eval_' . $slug . '_teacher', 'setValue' => false, 'setActive' => true, 'setLabel' => 'Autoriser l\'affichage des évaluations aux enseignants', 'setCategory' => 'Module Evaluation', 'setType' => 2, 'setMore' => null, 'setStructure' => $structure),
                15 => array('setName' => 'placement_' . $slug . '_active', 'setValue' => false, 'setActive' => true, 'setLabel' => 'Activer le module de gestion des stages', 'setCategory' => 'Module Stage', 'setType' => 2, 'setMore' => null, 'setStructure' => $structure),
                16 => array('setName' => 'reg_' . $slug . '_notification', 'setValue' => false, 'setActive' => true, 'setLabel' => 'Envoyer un mail de notification à chaque adhésion', 'setCategory' => 'Module Adhesion', 'setType' => 2, 'setMore' => null, 'setStructure' => $structure),
            );

            foreach ($parameters as $parameter) {
                if (!$this->em->getRepository('App:Parameter')->findByName($parameter['setName'])) {
                    $structure_parameter = new Parameter();
                    foreach ($parameter as $name => $value) {
                        $structure_parameter->$name($value);
                    }
                    $this->em->persist($structure_parameter);
                    $count++;
                }
            }
        }

        $this->em->flush();
        if ($count)
            $this->session->getFlashBag()->add('notice', $count . ' paramètres ajoutés pour ' . count($structures) . ' structures en base de données.');
        return $this->redirect($this->generateUrl('core_maintenance_correct_db_fees'));
    }

    /**
     * Correct missing fee from old parameters
     *
     * @Route("/db/fees", name="core_maintenance_correct_db_fees")
     */
    public function correctDBFeesAction()
    {
        $structures = $this->em->getRepository('App:Structure')->findAll();
        $count = 0;

        foreach ($structures as $structure) {
            $fees = $this->em->getRepository('App:Fee')->getForStructure($structure);
            if (!$fees) {
                $slug = $structure->getSlug();
                $fee = new Fee();
                $fee->setAmount($this->em->getRepository('App:Parameter')->findByName('reg_' . $slug . '_payment')->getValue()*100);
                $fee->setTitle("Normal");
                $fee->setStructure($structure);
                $fee->setDefault(true);
                $fee->setCounted(true);
                $this->em->persist($fee);
                $count++;
            }
        }

        $this->em->flush();
        if ($count)
            $this->session->getFlashBag()->add('notice', $count . ' tarifs ajoutés pour ' . count($structures) . ' structures en base de données.');
        return $this->redirect($this->generateUrl('core_maintenance_correct_db_amounts'));
    }

    /**
     * Correct missing fee_id for memberships with amounts
     *
     * @Route("/db/amount", name="core_maintenance_correct_db_amounts")
     */
    public function correctDBAmountsAction()
    {
        $fees = $this->em->getRepository('App:Fee')->getAllWithStructure();
        foreach ($fees as $fee) {
            $fees_table[$fee->getStructure()->getId()] = [$fee->getAmount() => $fee];
        }

        $memberships = $this->em->getRepository('App:Membership')->getAll();
        foreach ($memberships as $membership) {
            if (isset($fees_table[$membership->getStructure()->getId()][$membership->getAmount()])) {
                $membership->setFee($fees_table[$membership->getStructure()->getId()][$membership->getAmount()]);
                $this->em->persist($membership);
            }
        }
        $this->em->flush();

        return $this->redirect($this->generateUrl('app_structure_map'));
    }

    /**
     * Correct missing ROLE_MEMBER after migration from GESSEH
     *
     * @Route("/db/roles", name="core_maintenance_correct_db_roles")
     */
    public function correctDBRolesAction()
    {
        $count = 0;
        $users = $this->em->getRepository('App:User')->findAll();
        foreach ($users as $user) {
            if($user->hasRole('ROLE_STUDENT') and !$user->hasRole('ROLE_MEMBER')) {
                $user->addRole('ROLE_MEMBER');
                $this->em->persist($user);
                $count++;
            }
        }
        $this->em->flush();

        $this->session->getFlashBag()->add('notice', $count . ' droits corrigés en base de données.');
        return $this->redirect($this->generateUrl('core_maintenance_correct_db_persons'));
    }

    /**
     * Correct missing structure for person
     *
     * @Route("/db/persons", name="core_maintenance_correct_db_persons")
     */
    public function correctDBPersonsAction()
    {
        $count = 0;
        $persons = $this->em->getRepository('App:Person')->findBy(['structure' => null]);
        foreach ($persons as $person) {
            if ($membership = $this->em->getRepository('App:Membership')->getLastForPerson($person)) {
                $person->setStructure($membership->getStructure());
                $this->em->persist($person);
                $count++;
            }
        }
        $this->em->flush();

        $this->session->getFlashBag()->add('notice', $count . ' utilisateurs corrigés en base de données.');
        return $this->redirect($this->generateUrl('app_structure_map'));
    }
}
