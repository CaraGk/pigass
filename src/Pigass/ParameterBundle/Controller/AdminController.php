<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2013-2016 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Pigass\ParameterBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\DiExtraBundle\Annotation as DI,
    JMS\SecurityExtraBundle\Annotation as Security;
use Pigass\ParameterBundle\Form\ParametersType,
    Pigass\ParameterBundle\Form\ParametersHandler;

/**
 * ParameterAdmin controller.
 *
 * @Route("/")
 */
class AdminController extends Controller
{
    /** @DI\Inject */
    private $session;

    /** @DI\Inject("doctrine.orm.entity_manager") */
    private $em;

    /** @DI\Inject("kdb_parameters.manager") */
    private $pm;

    /** @DI\Inject("fos_user.user_manager") */
    private $um;

    /**
     * List all parameters and edit them
     *
     * @Route("/{slug}/param", name="parameter_admin_index")
     * @Template()
     * @Security\Secure(roles="ROLE_ADMIN, ROLE_STRUCTURE")
     */
    public function indexAction(Request $request, $slug)
    {
        if ($structure_filter = $this->session->get('slug') and !$this->um->findUserByUsername($username)->hasRole('ROLE_ADMIN')) {
            $structure = $this->em->getRepository('PigassCoreBundle:Structure')->findOneBy(array('slug' => $structure_filter));
        } else {
            $structure = $this->em->getRepository('PigassCoreBundle:Structure')->findOneBy(array('slug' => $slug));
        }
        if (!$structure)
            throw $this->createNotFoundException('Impossible de trouver une structure correspondante.');

        $parameters = $this->pm->findActiveParams(array('structure' => $structure->getId()));

        $form = $this->createForm(ParametersType::class, $parameters, array('parameters' => $parameters));
        $formHandler = new ParametersHandler($form, $request, $this->pm, $parameters);

        if ( $formHandler->process() ) {
            $this->get('session')->getFlashBag()->add('notice', 'Paramètres mis à jour.');

            return $this->redirect($this->generateUrl('parameter_admin_index', array('slug' => $slug)));
        }

        return array(
            'parameter_form' => $form->createView(),
    );
  }

}
