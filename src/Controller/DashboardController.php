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
        return $expire;
    }
}
