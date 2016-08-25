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

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pigass\ParameterBundle\Form\ParametersType;
use Pigass\ParameterBundle\Form\ParametersHandler;

/**
 * ParameterAdmin controller.
 *
 * @Route("/admin/param")
 */
class AdminController extends Controller
{
    /**
    * @Route("/", name="GParameter_PAIndex")
    * @Template()
    */
    public function indexAction()
    {
        $pm = $this->container->get('kdb_parameters.manager');
        $parameters = $pm->findParams();

        $form = $this->createForm(new ParametersType($parameters), $parameters);
        $formHandler = new ParametersHandler($form, $this->get('request'), $pm, $parameters);

        if ( $formHandler->process() ) {
            $this->get('session')->getFlashBag()->add('notice', 'Paramètres mis à jour.');

            return $this->redirect($this->generateUrl('GParameter_PAIndex'));
        }

        return array(
            'parameter_form' => $form->createView(),
    );
  }

}
