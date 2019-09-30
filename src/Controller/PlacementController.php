<?php

/**
 * This file is part of GESSEH project
 *
 * @author: Pierre-François ANGRAND <gesseh@medlibre.fr>
 * @copyright: Copyright 2013-2016 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route,
    Symfony\Component\Security\Core\Exception\AccessDeniedException,
    Symfony\Component\HttpFoundation\Session\SessionInterface,
    Symfony\Component\HttpFoundation\Request,
    Doctrine\ORM\EntityManagerInterface,
    FOS\UserBundle\Model\UserManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Security,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted,
    Symfony\Component\HttpFoundation\Response;
use App\Entity\Structure;
use App\Entity\Period,
    App\Form\PeriodType,
    App\Form\PeriodHandler;
use App\Entity\Placement,
    App\Form\PlacementType,
    App\Form\PlacementHandler;
use App\Entity\Repartition,
    App\Form\RepartitionsType,
    App\Form\RepartitionsHandler;


/**
 * Placement controller.
 */
class PlacementController extends AbstractController
{
    protected $session, $em, $um;

    public function __construct(SessionInterface $session, UserManagerInterface $um, EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->um = $um;
        $this->session = $session;
    }

    /**
     * @Route("/{slug}/placement/{id}", name="app_placement_list", requirements={"slug" = "\w+", "id" = "\d+"})
     * @Template
     */
    public function listPlacements($slug, Repartition $repartition, Request $request)
    {
        $placements = $this->em->getRepository('App:Placement')->getByRepartition($repartition);
        $structure = $this->em->getRepository('App:Structure')->findOneBy(['slug' => $slug]);

        return [
            'structure'  => $structure,
            'placements' => $placements,
        ];
    }

    /**
     * @Route("/{slug}/period", name="GCore_PAPeriodIndex")
     * @Template()
     */
    public function periodAction($slug)
    {
        $periods = $this->em->getRepository('App:Period')->findAll();

        return array(
            'slug'           => $slug,
            'periods'        => $periods,
            'period_id'      => null,
            'period_form'    => null,
        );
    }

    /**
     * @Route("/{slug}/period/new", name="GCore_PAPeriodNew")
     * @Template("App:PlacementAdmin:period.html.twig")
     */
    public function newPeriodAction($slug, Request $request)
    {
        $periods = $this->em->getRepository('App:Period')->findAll();
        $last_period = $this->em->getRepository('App:Period')->getLast();

        $period = new Period();
        $form = $this->createForm(PeriodType::class, $period);
        $formHandler = new PeriodHandler($form, $request, $this->em);

        if ( $formHandler->process() ) {
            $last_repartitions = $this->em->getRepository('App:Repartition')->getByPeriod($last_period);
            foreach($last_repartitions as $repartition) {
                $new_repartition = new Repartition();
                $new_repartition->setPeriod($period);
                $new_repartition->setDepartment($repartition->getDepartment());
                $new_repartition->setNumber($repartition->getNumber());
                $new_repartition->setCluster($repartition->getCluster());
                $this->em->persist($new_repartition);
          }
          $this->em->flush();

          $this->session->getFlashBag()->add('notice', 'Session "' . $period . '" enregistrée.');

          return $this->redirect($this->generateUrl('GCore_PAPeriodIndex'));
      }

      return array(
        'periods'        => $periods,
        'period_id'      => null,
        'period_form'    => $form->createView(),
      );
    }

    /**
     * @Route("/{slug}/period/{id}/edit", name="GCore_PAPeriodEdit", requirements={"id" = "\d+"})
     * @Template("App:PlacementAdmin:period.html.twig")
     */
    public function editPeriodAction($slug, Request $request, Period $period)
    {
      $paginator = $this->get('knp_paginator');
      $periods = $this->em->getRepository('App:Period')->findAll();

      $form = $this->createForm(PeriodType::class, $period);
      $formHandler = new PeriodHandler($form, $request, $this->em);

      if ( $formHandler->process() ) {
        $this->session->getFlashBag()->add('notice', 'Session "' . $period . '" modifiée.');

        return $this->redirect($this->generateUrl('GCore_PAPeriodIndex'));
      }

      return array(
        'periods'        => $periods,
        'period_id'      => $period->getId(),
        'period_form'    => $form->createView(),
      );
    }

    /**
     * @Route("/{slug}/period/{id}/delete", name="GCore_PAPeriodDelete", requirements={"id" = "\d+"})
     */
    public function deletePeriodeAction($slug, Period $period)
    {
      $this->em->remove($period);
      $this->em->flush();

      $this->session->getFlashBag()->add('notice', 'Session "' . $period . '" supprimée.');

      return $this->redirect($this->generateUrl('GCore_PAPeriodIndex'));
    }

    /**
     * @Route("/{slug}/placement", name="GCore_PAPlacementIndex")
     * @Template()
     */
    public function placementAction($slug, Request $request)
    {
      $limit = $request->query->get('limit', null);
      $paginator = $this->get('knp_paginator');
      $placements_query = $this->em->getRepository('App:Placement')->getAll($limit);
      $placements = $paginator->paginate( $placements_query, $request->query->get('page', 1), 20);

      $manager = $this->container->get('kdb_parameters.manager');
      $mod_eval = $this->em->getRepository('App:Parameter')->findByName('eval_active');
      if (true == $mod_eval->getValue()) { // Si les évaluations sont activées
        $evaluated = $this->em->getRepository('App:Evaluation')->getEvaluatedList('array');
      } else {
          $evaluated = null;
      }

      return array(
        'placements'     => $placements,
        'placement_id'   => null,
        'placement_form' => null,
        'evaluated'      => $evaluated,
        'limit'          => $limit,
      );
    }

    /**
     * @Route("/{slug}/placement/{id}/edit", name="GCore_PAPlacementEdit", requirements={"id" = "\d+"})
     * @Template("App:PlacementAdmin:edit.html.twig")
     */
    public function editPlacementAction($slug, Request $request, Placement $placement)
    {
      $limit = $request->query->get('limit', null);
      $paginator = $this->get('knp_paginator');
      $placements_query = $this->em->getRepository('App:Placement')->getAll($limit);
      $placements = $paginator->paginate( $placements_query, $request->query->get('page', 1), 20);

      $form = $this->createForm(PlacementType::class, $placement);
      $formHandler = new PlacementHandler($form, $request, $this->em);

      if ( $formHandler->process() ) {
        $this->session->getFlashBag()->add('notice', 'Stage "' . $placement->getPerson() . ' : ' . $placement->getRepartition()->getDepartment() . $placement->getRepartition()->getPeriod() . '" modifié.');

        return $this->redirect($this->generateUrl('GCore_PAPlacementIndex'));
      }

      $manager = $this->container->get('kdb_parameters.manager');
      $mod_eval = $this->em->getRepository('App:Parameter')->findByName('eval_active');
      if (true == $mod_eval->getValue()) { // Si les évaluations sont activées
        $evaluated = $this->em->getRepository('App:Evaluation')->getEvaluatedList('array');
      } else {
          $evaluated = null;
      }

      return array(
        'placements'     => $placements,
        'placement_id'   => $placement->getId(),
        'placement_form' => $form->createView(),
        'evaluated'      => $evaluated,
        'limit'          => $limit,
      );
    }

    /**
     * @Route("/{slug}/placement/new", name="GCore_PAPlacementNew")
     * @Template("App:PlacementAdmin:edit.html.twig")
     */
    public function newPlacementAction($slug, Request $request)
    {
      $limit = $request->query->get('limit', null);
      $paginator = $this->get('knp_paginator');
      $placements_query = $this->em->getRepository('App:Placement')->getAll($limit);
      $placements = $paginator->paginate( $placements_query, $request->query->get('page', 1), 20);

      $form = $this->createForm(PlacementType::class);
      $formHandler = new PlacementHandler($form, $request, $this->em);

      if ( $placement = $formHandler->process() ) {
        $this->session->getFlashBag()->add('notice', 'Stage de '. $placement->getPerson() . ' à ' . $placement->getRepartition()->getDepartment() . ' en ' . $placement->getRepartition()->getPeriod() . '" enregistré.');

        return $this->redirect($this->generateUrl('GCore_PAPlacementIndex'));
      }

      $manager = $this->container->get('kdb_parameters.manager');
      $mod_eval = $this->em->getRepository('App:Parameter')->findByName('eval_active');
      if (true == $mod_eval->getValue()) { // Si les évaluations sont activées
        $evaluated = $this->em->getRepository('App:Evaluation')->getEvaluatedList('array');
      } else {
          $evaluated = null;
      }

      return array(
        'placements'     => $placements,
        'placement_id'   => null,
        'placement_form' => $form->createView(),
        'evaluated'      => $evaluated,
        'limit'          => $limit,
      );
    }

    /**
     * @Route("/{slug}/placement/{id}/delete", name="GCore_PAPlacementDelete", requirements={"id" = "\d+"})
     */
    public function deletePlacementAction($slug, Request $request, Placement $placement)
    {
      $limit = $request->query->get('limit', null);

      $this->em->remove($placement);
      $this->em->flush();

      $this->session->getFlashBag()->add('notice', 'Stage "' . $placement->getPerson() . ' : ' . $placement->getRepartition()->getDepartment() . $placement->getRepartition()->getPeriod() . '" supprimé.');

      return $this->redirect($this->generateUrl('GCore_PAPlacementIndex'));
    }

    /**
     * @Route("/{slug}/period/{id}/repartitions", name="GCore_PARepartitionsPeriod", requirements={"id" = "\d+"})
     * @Template("App:PlacementAdmin:repartitionsEdit.html.twig")
     */
    public function repartitionsForPeriodEditAction($slug, Request $request, Period $period)
    {
        $paginator = $this->get('knp_paginator');

        $hospital_id = $request->query->get('hospital_id', 0);
        $hospital_count = $request->query->get('hospital_count', 0);
        $next_hospital = $this->em->getRepository('App:Hospital')->getNext($hospital_id);
        $hospital_total = $this->em->getRepository('App:Hospital')->countAll();

        if (!$next_hospital)
            return $this->redirect($this->generateUrl('GCore_PAPeriodIndex'));

        $repartitions = $this->em->getRepository('App:Repartition')->getByPeriod($period, $next_hospital->getId());

        $form = $this->createForm(RepartitionsType, $repartitions, ['period' => $period, 'repartitions' => $repartitions]);
        $form_handler = new RepartitionsHandler($form, $request, $this->em, $repartitions);
        if ($form_handler->process()) {
            $hospital_count;
            $this->session->getFlashBag()->add('notice', 'Répartition pour la période "' . $period . '" enregistrée (' . $hospital_count . '/' . $hospital_total . ').');

            return $this->redirect($this->generateUrl('GCore_PARepartitionsPeriod', array(
                'id'      => $period->getId(),
                'hospital_id'    => $next_hospital->getId(),
                'hospital_count' => $hospital_count,
            )));
        }

        return array(
            'origin' => $period->getName() . ' : ' . $next_hospital->getName(),
            'form'   => $form->createView(),
        );
    }

    /**
     * @Route("/{slug}/department/{department_id}/repartitions", name="GCore_PARepartitionsDepartment", requirements={"department_id" = "\d+"})
     * @Template("App:PlacementAdmin:repartitionsEdit.html.twig")
     */
    public function repartitionsForDepartmentEditAction($slug, Request $request, $department_id)
    {
        $department = $this->em->getRepository('App:Department')->find($department_id);

        if(!$department)
            throw $this->createNotFoundException('Unable to find department entity.');

        $repartitions = $this->em->getRepository('App:Repartition')->getByDepartment($department_id);
        $periods = $this->em->getRepository('App:Period')->findAll();
        if (count($repartitions) < count($periods)) {
            return $this->redirect($this->generateUrl('GCore_PARepartitionsDepartmentMaintenance', array(
                'department_id' => $department->getId(),
            )));
        }

        $form = $this->createForm(RepartitionsType, $repartitions, ['period' => $period, 'repartitions' => $repartitions]);
        $form_handler = new RepartitionsHandler($form, $request, $this->em, $repartitions);
        if ($form_handler->process()) {
            $this->session->getFlashBag()->add('notice', 'Répartition pour le terrain "' . $department . '" enregistrée.');

            return $this->redirect($this->generateUrl('GCore_FSIndex'));
        }

        return array(
            'origin'     => $department,
            'form' => $form->createView(),
        );
    }

    /**
     * Maintenance tasks
     *
     * @Route("/{slug}/maintenance/department", name="GCore_PAMaintenance")
     * @Template()
     */
    public function maintenanceAction($slug, Request $request)
    {
        $departments = $this->em->getRepository('App:Department')->getAllInArray();

        return array(
            'departments' => $departments,
        );
    }

    /**
     * Maintenance for department's repartitions
     *
     * @Route("/{slug}/department/repartitions/", name="GCore_PARepartitionsDepartmentMaintenance")
     */
    public function repartitionsForDepartmentMaintenanceAction($slug, Request $request)
    {
        $periods = $this->em->getRepository('App:Period')->findAll();
        $department = $this->em->getRepository('App:Department')->find($request->get('department_id'));

        if(!$department) {
            if ($request->isXmlHttpRequest())
                return new JsonResponse(array('message' => 'Error: Unknown entity.'), 404);
            else
                throw $this->createNotFoundException('Unable to find department entity.');
        }

        $count = 0;
        foreach ($periods as $period) {
            if (!$this->em->getRepository('App:Repartition')->getByPeriodAndDepartment($period, $department->getId())) {
                $repartition = new Repartition();
                $repartition->setDepartment($department);
                $repartition->setPeriod($period);
                $repartition->setNumber(0);
                $this->em->persist($repartition);
                $count++;
            }
        }
        $this->em->flush();

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(array(
                'message' => $count,
            ), 200);
        } else {
            $this->session->getFlashBag()->add('notice', 'Maintenance : ' . $department . ' -> ' . $count . ' répartition(s) ajoutée(s)');

            return $this->redirect($this->generateUrl('GCore_PARepartitionsDepartment', array(
                'department_id' => $department->getId(),
            )));
        }
    }
}
