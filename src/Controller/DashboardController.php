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
     * Admin dashboard
     *
     * @Route("/{slug}/admin", name="app_dashboard_admin", requirements={"slug" = "\w+"})
     * @Template
     * @Security("is_granted('ROLE_ADMIN') or (is_granted('ROLE_STRUCTURE') and  is_granted(structure.getRole()))")
     */
    public function admin(Structure $structure, Request $request)
    {
        $me = $this->em->getRepository('App:Person')->getByUser($this->getUser());

        $fees = $this->em->getRepository('App:Fee')->findByStructure($structure);
        foreach ($fees as $fee) {
            $modules['adhesion']['count_validated']['fees'][$fee->getTitle()] = count($this->em->getRepository('App:Membership')->getCurrentByStructure($structure->getSlug(), [
                'valid' => true,
                'fee'   => $fee->getId(),
            ]));
        }
        $modules['adhesion']['count_validated']['total'] = count($this->em->getRepository('App:Membership')->getCurrentByStructure($structure->getSlug(), [
            'valid' => true,
        ]));
        $modules['adhesion']['count_unvalidated']['total'] = count($this->em->getRepository('App:Membership')->getCurrentByStructure($structure->getSlug(), [
            'valid' => false,
        ]));

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

        $structures = $this->em->getRepository('App:Structure')->getAll(true);
        foreach ($structures as $structure) {
            $modules['adhesion']['count_validated']['structures'][$structure->getName()] = count($this->em->getRepository('App:Membership')->getCurrentByStructure($structure->getSlug(), [
                'valid' => true,
            ]));
            $modules['adhesion']['count_unvalidated']['structures'][$structure->getName()] = count($this->em->getRepository('App:Membership')->getCurrentByStructure($structure->getSlug(), [
                'valid' => false,
            ]));
        }
        $modules['adhesion']['count_validated']['total'] = count($this->em->getRepository('App:Membership')->getCurrentByStructure(null, [
            'valid' => true,
        ]));

        return [
            'me'      => $me,
            'modules' => $modules,
        ];
    }
}
