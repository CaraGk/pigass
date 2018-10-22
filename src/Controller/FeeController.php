<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2017-2018 Pierre-François Angrand
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
    Symfony\Component\HttpFoundation\File\File,
    Symfony\Component\HttpFoundation\Response;
use App\Entity\Fee,
    App\Form\FeeType,
    App\FormHandler\FeeHandler,
    App\Entity\Structure,
    App\Entity\Membership;

/**
 * Fee controller.
 *
 * @Route("/")
 */
class FeeController extends AbstractController
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
     * List the fees for fee
     *
     * @Route("/{slug}/fee", name="core_fee_index", requirements={"slug" = "\w+"})
     * @Template()
     */
    public function indexAction($slug)
    {
        if (!($this->security->isGranted('ROLE_STRUCTURE') or $this->security->isGranted('ROLE_ADMIN')))
            throw new AccessDeniedException();

        $user = $this->getUser();
        if (!$user->hasRole('ROLE_ADMIN')) {
            $slug = $this->session->get('slug');
        }
        $structure = $this->em->getRepository('App:Structure')->findOneBy(array('slug' => $slug));

        if (!$structure)
            throw $this->createNotFoundException('Structure inconnue');

        $fees = $this->em->getRepository('App:Fee')->getForStructure($structure);

        return array(
            'structure' => $structure,
            'fees'  => $fees,
        );
    }

    /**
     * Add a new fee
     *
     * @Route("/{slug}/fee/new", name="core_fee_new", requirements={"slug" = "\w+"})
     * @Template("fee/edit.html.twig")
     */
    public function newAction($slug, Request $request)
    {
        if (!($this->security->isGranted('ROLE_STRUCTURE') or $this->security->isGranted('ROLE_ADMIN')))
            throw new AccessDeniedException();

        $user = $this->getUser();
        if (!$user->hasRole('ROLE_ADMIN')) {
            $slug = $this->session->get('slug');
        }
        $structure = $this->em->getRepository('App:Structure')->findOneBy(array('slug' => $slug));

        if (!$structure)
            throw $this->createNotFoundException('Structure inconnue');

        $fee = new Fee();
        $form = $this->createForm(feeType::class, $fee, array('structure' => $structure));
        $formHandler = new FeeHandler($form, $request, $this->em, $structure);

        if ($formHandler->process()) {
            $this->session->getFlashBag()->add('notice', 'Tarification pour "' . $structure . '" enregistrée.');
            return $this->redirect($this->generateUrl('core_fee_index', array('slug' => $structure->getSlug())));
        }

        return array(
            'form'  => $form->createView(),
            'fee'   => null,
        );
    }

    /**
     * Edit a fee
     *
     * @Route("/{slug}/fee/{id}/edit", name="core_fee_edit", requirements={"slug" = "\w+", "id" = "\d+"})
     * @Template("fee/edit.html.twig")
     */
    public function editAction($slug, Fee $fee, Request $request)
    {
        if (!($this->security->isGranted('ROLE_STRUCTURE') or $this->security->isGranted('ROLE_ADMIN')))
            throw new AccessDeniedException();

        $user = $this->getUser();
        if (!$user->hasRole('ROLE_ADMIN')) {
            $slug = $this->session->get('slug');
            $structure = $this->em->getRepository('App:Structure')->findOneBy(array('slug' => $slug));
        }

        $structure = $this->em->getRepository('App:Structure')->findOneBy(array('slug' => $slug));

        if (!$structure) {
            $this->session->getFlashBag()->add('error', 'La fee n\'existe pas ou vous n\'y avez pas accès.');
            return $this->redirect($this->generateUrl('core_fee_index', array('slug' => $slug)));
        }

        $form = $this->createForm(FeeType::class, $fee, array('structure' => $structure));
        $formHandler = new FeeHandler($form, $request, $this->em, $structure);

        if ($oldName = $formHandler->process()) {
            $parameters = $this->em->getRepository('App:Parameter')->getBySlug($oldName);

            $this->em->flush();

            $this->session->getFlashBag()->add('notice', 'fee "' . $fee . '" modifiée.');

            return $this->redirect($this->generateUrl('core_fee_index', array('slug' => $slug)));
        }

        return array(
            'form'      => $form->createView(),
            'fee' => $fee,
        );
    }

    /**
     * Delete a fee
     *
     * @Route("/{slug}/fee/{id}/delete", name="core_fee_delete", requirements={"id" = "\d+", "slug" = "\w+"})
     */
    public function deleteAction($slug, Fee $fee)
    {
        if (!($this->security->isGranted('ROLE_STRUCTURE') or $this->security->isGranted('ROLE_ADMIN')))
            throw new AccessDeniedException();

        $this->em->remove($fee);
        $this->em->flush();

        $this->session->getFlashBag()->add('notice', 'Tarification "' . $fee . '" supprimée.');
        return $this->redirect($this->generateUrl('core_fee_index', ['slug' => $slug]));
    }

}
