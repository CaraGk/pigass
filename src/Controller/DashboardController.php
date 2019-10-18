<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2018 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route,
    Symfony\Component\HttpFoundation\Session\SessionInterface,
    Symfony\Component\HttpFoundation\Request,
    Doctrine\ORM\EntityManagerInterface,
    FOS\UserBundle\Model\UserManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Security,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted,
    Symfony\Component\HttpFoundation\Response;
use App\Entity\Structure;

/**
 * Dashboard controller.
 *
 * @Route("/")
 */
class DashboardController extends AbstractController
{
    protected $session, $em, $um;

    public function __construct(SessionInterface $session, UserManagerInterface $um, EntityManagerInterface $em)
    {
        $this->session = $session;
        $this->um = $um;
        $this->em = $em;
    }

    /**
     * Person dashboard
     *
     * @Route("/{slug}/user", name="app_dashboard_user", requirements={"slug" = "\w+"})
     * @Template
     * @security("is_granted('ROLE_PERSON') or is_granted('ROLE_STUDENT') or is_granted('ROLE_STRUCTURE') or is_granted('ROLE_ADMIN')")
     */
    public function user(Structure $structure, Request $request)
    {
        $user = $this->getUser();
        $filter = $this->session->get('user_register_filter', null);
        $userid = isset($filter['user'])?$filter['user']:$request->query->get('userid');
        $person = $this->testAdminTakeOver($user, $userid);

        /* Load recent memberships */
        $modules['adhesion']['current'] = $this->em->getRepository('App:Membership')->getCurrentForPerson($person);
        $modules['adhesion']['last'] = $this->em->getRepository('App:Membership')->getLastForPerson($person);
        $modules['adhesion']['rejoinable'] = false;

        /* Test memberships and rejoinability */
        if ($modules['adhesion']['current']) {
            $modules['adhesion']['recent'] = $modules['adhesion']['current'];
            $structure = $modules['adhesion']['recent']->getStructure();
            $now = new \DateTime('now');
            $now->modify($this->em->getRepository('App:Parameter')->findByName('reg_' . $structure->getSlug() . '_anticipated')->getValue());
            if ($modules['adhesion']['current']->getExpiredOn() <= $now and $modules['adhesion']['current']->getStatus() != 'excluded') {
                $modules['adhesion']['rejoinable'] = true;
            }
        } elseif ($modules['adhesion']['last']) {
            $modules['adhesion']['recent'] = $modules['adhesion']['last'];
            $structure = $modules['adhesion']['recent']->getStructure();
            if ($modules['adhesion']['last']->getStatus() != 'excluded')
                $modules['adhesion']['rejoinable'] = true;
        } else {
            $modules['adhesion']['rejoinable'] = true;
        }

        /* Load memberships */
        $modules['adhesion']['memberships'] = $this->em->getRepository('App:Membership')->findBy(['person' => $person]);

        /* Load placements */
        $modules['stages']['placements'] = $this->em->getRepository('App:Placement')->getByPerson($person);
        $modules['stages']['evaluated'] = array();
        if (true == $this->em->getRepository('App:Parameter')->findByName('eval_' . $structure->getSlug() . '_active')->getValue())
            $modules['stages']['evaluated'] = $this->em->getRepository('App:Evaluation')->getEvaluatedList('array', $person);

        return [
            'structure'   => $structure,
            'person'      => $person,
            'userid'      => $userid,
            'modules'     => $modules,
        ];

    }

    /**
     * Admin dashboard
     *
     * @Route("/{slug}/admin", name="app_dashboard_admin", requirements={"slug" = "\w+"})
     * @Template
     * @Security("is_granted('ROLE_ADMIN') or (is_granted('ROLE_STRUCTURE') and  is_granted(structure.getRole()))")
     */
    public function admin(Structure $structure, Request $request)
    {
        $me = $this->em->getRepository('App:Person')->getByUser($this->getUser());

        /* Adminsitrateurs */
        $modules['users']['superadmins'] = $this->em->getRepository('App:Person')->getByRole('ROLE_ADMIN', null);
        $modules['users']['structure'] = $this->em->getRepository('App:Person')->getByRole('ROLE_STRUCTURE', $structure);

        /* Adhésions */
        $date = new \DateTime($request->query->get('date', 'now'));
        $expire = $this->getExpirationDate($structure, $date);

        $fees = $this->em->getRepository('App:Fee')->findByStructure($structure);
        foreach ($fees as $fee) {
            $modules['adhesion']['count_validated']['fees'][$fee->getTitle()] = count($this->em->getRepository('App:Membership')->getByStructure($structure->getSlug(), $expire, [
                'valid' => true,
                'fee'   => $fee->getId(),
            ]));
        }
        $gateways = $this->em->getRepository('App:Gateway')->findByStructure($structure);
        foreach ($gateways as $gateway) {
            $modules['adhesion']['count_validated']['gateways'][$gateway->getLabel()] = count($this->em->getRepository('App:Membership')->getByStructure($structure->getSlug(), $expire, [
                'valid'   => true,
                'gateway' => $gateway->getGatewayName(),
            ]));
        }
        $modules['adhesion']['count_validated']['total'] = count($this->em->getRepository('App:Membership')->getByStructure($structure->getSlug(), $expire, [
            'valid' => true,
            'isCounted' => true,
        ]));
        $modules['adhesion']['count_unvalidated']['total'] = count($this->em->getRepository('App:Membership')->getByStructure($structure->getSlug(), $expire, [
            'valid' => false,
            'isCounted' => true,
        ]));
        $modules['adhesion']['count_uncounted']['total'] = count($this->em->getRepository('App:Membership')->getByStructure($structure->getSlug(), $expire, [
            'isCounted' => false,
        ]));
        $periodicity = $this->em->getRepository('App:Parameter')->findByName('reg_' . $structure->getSlug() . '_periodicity')->getValue();
        $modules['adhesion']['date'] = [
            'now'      => $date->format("Y-m-d"),
            'next'     => $date->modify($periodicity)->format("Y-m-d"),
            'previous' => $date->modify('- ' . substr($periodicity, 1))->modify('- ' . substr($periodicity, 1))->format("Y-m-d"),
        ];

        /* Stages */
        $modules['stage']['count'] = $this->em->getRepository('App:Person')->countAll($structure, true);
        $modules['stage']['sectors'] = $this->em->getRepository('App:Sector')->getAll($structure);
        $modules['stage']['hospitals'] = $this->em->getRepository('App:Hospital')->countAll($structure);

        return [
            'structure' => $structure,
            'me'        => $me,
            'modules'   => $modules,
        ];
    }

    /**
     * Superadmin dashboard
     *
     * @Route("/admin", name="app_dashboard_superadmin")
     * @Template
     * @IsGranted("ROLE_ADMIN")
     */
    public function superadmin(Request $request)
    {
        $me = $this->em->getRepository('App:Person')->getByUser($this->getUser());

        $modules['users']['superadmins'] = $this->em->getRepository('App:Person')->getByRole('ROLE_ADMIN', null);

        $structures = $this->em->getRepository('App:Structure')->getAll(true);
        foreach ($structures as $structure) {
            $modules['users']['structures'][$structure->getSlug()] = $this->em->getRepository('App:Person')->getByRole('ROLE_STRUCTURE', $structure);

            $modules['adhesion']['count_validated']['structures'][$structure->getSlug()] = count($this->em->getRepository('App:Membership')->getCurrentByStructure($structure->getSlug(), [
                'valid'     => true,
                'isCounted' => true,
            ]));
            $modules['adhesion']['count_unvalidated']['structures'][$structure->getSlug()] = count($this->em->getRepository('App:Membership')->getCurrentByStructure($structure->getSlug(), [
                'valid'     => false,
                'isCounted' => true,
            ]));
            $modules['adhesion']['count_uncounted']['structures'][$structure->getSlug()] = count($this->em->getRepository('App:Membership')->getCurrentByStructure($structure->getSlug(), [
                'isCounted' => false,
            ]));
        }
        $modules['adhesion']['count_validated']['total'] = count($this->em->getRepository('App:Membership')->getCurrentByStructure(null, [
            'valid'     => true,
            'isCounted' => true,
        ]));
        $modules['adhesion']['count_unvalidated']['total'] = count($this->em->getRepository('App:Membership')->getCurrentByStructure(null, [
            'valid'     => false,
            'isCounted' => true,
        ]));
        $modules['adhesion']['count_uncounted']['total'] = count($this->em->getRepository('App:Membership')->getCurrentByStructure(null, [
            'isCounted' => false,
        ]));

        return [
            'me'      => $me,
            'modules' => $modules,
        ];
    }

    /**
     * Test for admin take over function
     *
     * @return Person
     */
    private function testAdminTakeOver($user, $user_id = null)
    {
        if (($user->hasRole('ROLE_ADMIN') or $user->hasRole('ROLE_STRUCTURE')) and $user_id != null) {
            $user_taken_over = $this->um->findUserBy(array(
                'id' => $user_id,
            ));

            if (!$user_taken_over)
                throw $this->createAccessDeniedException('Vous n\'avez pas les autorisations pour accéder à cette fiche.');

            $person = $this->em->getRepository('App:Person')->getByUsername($user_taken_over->getUsername());

            if (!$user->hasRole('ROLE_ADMIN')) {
                $membership = $this->em->getRepository('App:Membership')->getLastForPerson($person);
                if ($membership and $membership->getStructure()->getSlug() != $this->session->get('slug'))
                    throw $this->createAccessDeniedException('Vous n\'avez pas les autorisations pour accéder à cette fiche.');
            }
        } else {
            $person = $this->em->getRepository('App:Person')->getByUsername($user->getUsername());
        }

        return $person;
    }

    private function getExpirationDate(Structure $structure, \DateTime $date)
    {
        $init = $this->em->getRepository('App:Parameter')->findByName('reg_' . $structure->getSlug() . '_date')->getValue();
        $periodicity = $this->em->getRepository('App:Parameter')->findByName('reg_' . $structure->getSlug() . '_periodicity')->getValue();
        $anticipated = $this->em->getRepository('App:Parameter')->findByName('reg_' . $structure->getSlug() . '_anticipated')->getValue();
        $expire = new \DateTime($init);
        $expire->modify('- 1 day');
        $date->modify($anticipated);
        while ($expire <= $date) {
            $expire->modify($periodicity);
	}
	$date->modify('-' . $anticipated);
        return $expire;
    }
}
