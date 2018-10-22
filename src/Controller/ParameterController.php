<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2013-2018 Pierre-François Angrand
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
    Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Form\ParametersType,
    App\FormHandler\ParametersHandler;

/**
 * Parameter controller.
 *
 * @Route("/")
 */
class ParameterController extends AbstractController
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
     * List all parameters and edit them
     *
     * @Route("/{slug}/param", name="parameter_admin_index", requirements={"slug" = "\w+"})
     * @Template()
     */
    public function indexAction(Request $request, $slug, TokenStorageInterface $tokenStorage)
    {
        if (!($this->security->isGranted('ROLE_STRUCTURE') or $this->security->isGranted('ROLE_ADMIN')))
            throw new AccessDeniedException();

        $username = $tokenStorage->getToken()->getUsername();
        if ($structure_filter = $this->session->get('slug') and !$this->um->findUserByUsername($username)->hasRole('ROLE_ADMIN')) {
            $structure = $this->em->getRepository('App:Structure')->findOneBy(array('slug' => $structure_filter));
        } else {
            $structure = $this->em->getRepository('App:Structure')->findOneBy(array('slug' => $slug));
        }
        if (!$structure)
            throw $this->createNotFoundException('Impossible de trouver une structure correspondante.');

        $parameters = $this->em->getRepository('App:Parameter')->findBy(['structure' => $structure->getId(), 'active' => true]);

        $form = $this->createForm(ParametersType::class, $parameters, array('parameters' => $parameters));
        $formHandler = new ParametersHandler($form, $request, $this->em, $parameters);

        if ( $formHandler->process() ) {
            $this->session->getFlashBag()->add('notice', 'Paramètres mis à jour.');

            return $this->redirect($this->generateUrl('parameter_admin_index', array('slug' => $slug)));
        }

        return array(
            'parameter_form' => $form->createView(),
            'structure'      => $structure,
    );
  }

}
