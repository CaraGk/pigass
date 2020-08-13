<?php

/**
 * This file is part of GESSEH project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2013-2020 Pierre-François Angrand
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
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity,
    Symfony\Component\HttpFoundation\File\File,
    Symfony\Component\HttpFoundation\Response;
use App\Entity\Department,
    App\Entity\Wish,
    App\Entity\Simulation,
    App\Entity\Period,
    App\Entity\Structure,
    App\Entity\SimulPeriod,
    App\Form\WishType,
    App\FormHandler\WishHandler;

/**
 * Simulation controller
 *
 * @Route("/")
 */
class SimulationController extends AbstractController
{
    protected $session, $em, $um;

    public function __construct(SessionInterface $session, UserManagerInterface $um, EntityManagerInterface $em) {
        $this->session = $session;
        $this->em = $em;
        $this->um = $um;
    }

    /**
     * @Route("/{slug}/simulation", name="GSimul_SIndex", requirements={"slug" = "\w+"})
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
     * @Template()
     */
    public function indexAction(Structure $structure, Request $request)
    {
        $user = $this->getUser();
        $person_id = $request->get('person_id');
        $simulation = $this->testAdminTakeOver($user, $person_id);

        if(!$simulation) {
            $this->session->getFlashBag()->add('error', 'Vous ne participez pas aux simulations. Contacter l\'administrateur du site si vous pensez que cette situation est anormale.');
            return $this->redirect($this->generateUrl('app_dashboard_user', ['slug' => $structure->getSlug()]));
        }

        $last_period = $this->em->getRepository('App:Period')->getLast($structure);
        $wishes = $this->em->getRepository('App:Wish')->getByPerson($simulation->getPerson(), $last_period->getId());
        $rules = $this->em->getRepository('App:SectorRule')->getForPerson($simulation, $last_period, $this->em);
        $missing = $this->em->getRepository('App:Simulation')->countMissing($simulation);

        $new_wish = new Wish();
        $form = $this->createForm(WishType::class, $new_wish, ['rules' => $rules]);
        $formHandler = new WishHandler($form, $request, $this->em, $simulation);

        if ($formHandler->process()) {
            $this->session->getFlashBag()->add('notice', 'Nouveau vœu : "' . $new_wish->getDepartment() . '" enregistré.');

            return $this->redirect($this->generateUrl('app_dashboard_user', array('person_id' => $person_id, 'slug' => $structure->getSlug())));
        }

        if ($person_id != null) {
            $simname = $this->em->getRepository('App:Person')->find($person_id);
        } else {
            $simname = null;
        }

        return array(
            'wishes'     => $wishes,
            'wish_form'  => $form->createView(),
            'simulation' => $simulation,
            'missing'    => $missing,
            'person_id'      => $person_id,
            'simname'    => $simname,
            'structure'   => $structure,
        );
    }

    /**
     * @Route("/{slug}/simulation/wish/{wish_id}/up", name="GSimul_SUp", requirements={"wish_id" = "\d+", "slug" = "\w+"})
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
     */
    public function setWishUpAction(Structure $structure, $wish_id, Request $request)
    {
        $user = $this->getUser();
        $person_id = $request->get('person_id');
        $simulation = $this->testAdminTakeOver($user, $person_id);

        $wish = $this->em->getRepository('App:Wish')->findByPersonAndId($simulation->getPerson(), $wish_id);

        if(!$wish)
            throw $this->createNotFoundException('Unable to find Wish entity');

        if (!$this->em->getRepository('App:Period')->getSimulationActive($structure))
            $period = $this->em->getRepository('App:SimulPeriod')->getLast()->getPeriod();
        else
            $period = $this->em->getRepository('App:SimulPeriod')->getActive()->getPeriod();

        $rank = $wish->getRank();
        if ($rank > 1) {
            $wishes_before = $this->em ->getRepository('App:Wish')->findByPersonAndRank($simulation->getPerson(), $rank - 1, $period);
            foreach ($wishes_before as $wish_before) {
                $wish_before->setRank($rank);
                $this->em->persist($wish_before);
                $rank--;
              }
            $wish->setRank($rank);
            $this->em->persist($wish);
            $this->session->getFlashBag()->add('notice', 'Vœu : "' . $wish->getDepartment() . '" mis à jour.');
        } else {
            $this->session->getFlashBag()->add('error', 'Attention : le vœu "' . $wish->getDepartment() . '" est déjà le premier de la liste !');
        }
      $this->em->flush();

      return $this->redirect($this->generateUrl('app_dashboard_user', array('person_id' => $person_id, 'slug' => $structure->getSlug())));
    }

    /**
     * @Route("/{slug}/simulation/wish/{wish_id}/down", name="GSimul_SDown", requirements={"wish_id" = "\d+", "slug" = "\w+"})
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
     */
    public function setWishDownAction(Structure $structure, $wish_id, Request $request)
    {
        $user = $this->getUser();
        $person_id = $request->get('person_id');
        $simulation = $this->testAdminTakeOver($user, $person_id);

        $wish = $this->em->getRepository('App:Wish')->findByPersonAndId($simulation->getPerson(), $wish_id);

        if(!$wish)
          throw $this->createNotFoundException('Unable to find Wish entity');

        if (!$this->em->getRepository('App:Period')->getSimulationActive($structure))
            $period = $this->em->getRepository('App:SimulPeriod')->getLast()->getPeriod();
        else
            $period = $this->em->getRepository('App:SimulPeriod')->getActive()->getPeriod();

        $rank = $wish->getRank();
        $max_rank = $this->em->getRepository('App:Wish')->getMaxRank($simulation->getPerson());
        if ($rank < $max_rank) {
          $wishes_after = $this->em ->getRepository('App:Wish')->findByPersonAndRank($simulation->getPerson(), $rank + 1, $period);
          foreach ($wishes_after as $wish_after) {
            $wish_after->setRank($rank);
            $this->em->persist($wish_after);
            $rank++;
          }
          $wish->setRank($rank);
          $this->em->persist($wish);
          $this->session->getFlashBag()->add('notice', 'Vœu : "' . $wish->getDepartment() . '" mis à jour.');
        } else {
          $this->session->getFlashBag()->add('error', 'Attention : le vœu "' . $wish->getDepartment() . '" est déjà le dernier de la liste !');
        }
        $this->em->flush();

        return $this->redirect($this->generateUrl('app_dashboard_user', array('person_id' => $person_id, 'slug' => $structure->getSlug())));
    }

    /**
     * @Route("/{slug}/simulation/{wish_id}/delete", name="GSimul_SDelete", requirements={"wish_id" = "\d+", "slug" = "\w+"})
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
     */
    public function deleteAction(Structure $structure, $wish_id, Request $request)
    {
        $user = $this->getUser();
        $person_id = $request->get('person_id');
        $simulation = $this->testAdminTakeOver($user, $person_id);

        $wish = $this->em->getRepository('App:Wish')->findByPersonAndId($simulation->getPerson(), $wish_id);

        if(!$wish)
            throw $this->createNotFoundException('Unable to find Wish entity');

        $rank = $wish->getRank();
        $wishes_after = $this->em->getRepository('App:Wish')->findByRankAfter($simulation->getPerson(), $rank);
        foreach ($wishes_after as $wish_after) {
            $wish_after->setRank($wish_after->getRank()-1);
            $this->em->persist($wish_after);
        }
        $this->em->remove($wish);

        if ($simulation->countWishes() <= 1) {
            $simulation->setDepartment(null);
            $simulation->setExtra(null);
            $this->em->persist($simulation);
        }

        $this->em->flush();

        $this->session->getFlashBag()->add('notice', 'Vœu : "' . $wish->getDepartment() . '" supprimé.');

        return $this->redirect($this->generateUrl('app_dashboard_user', array('person_id' => $person_id, 'slug' => $structure->getSlug())));
    }

    /**
     * @Route("/{slug}/simulation/out", name="GSimul_SGetout", requirements={"slug" = "\w+"})
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
     */
    public function getoutAction(Structure $structure, Request $request)
    {
        $user = $this->getUser();
        $person_id = $request->get('person_id');
        $simulation = $this->testAdminTakeOver($user, $person_id);

      $simulation->setActive(false);
      $simulation->setDepartment(null);
      $simulation->setExtra(null);
      $this->em->persist($simulation);
      $this->em->flush();

      $this->session->getFlashBag()->add('notice', 'Vous ne participez plus à la simulation. Tous vos vœux ont été effacés.');

      return $this->redirect($this->generateUrl('app_dashboard_user', array('person_id' => $person_id, 'slug' => $structure->getSlug())));
    }

    /**
     * @Route("/{slug}/simulation/in", name="GSimul_SGetin", requirements={"slug" = "\w+"})
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
     */
    public function getinAction(Structure $structure, Request $request)
    {
        $user = $this->getUser();
        $person_id = $request->get('person_id');
        $simulation = $this->testAdminTakeOver($user, $person_id);

      $simulation->setActive(true);
      $this->em->persist($simulation);

      $this->em->flush();

      $this->session->getFlashBag()->add('notice', 'Vous pouvez désormais faire vos choix pour la simulation.');

      return $this->redirect($this->generateUrl('app_dashboard_user', array('person_id' => $person_id, 'slug' => $structure->getSlug())));
    }

    /**
     * @Route("/{slug}/simulation/do", name="GSimul_SSim", requirements={"slug" = "\w+"})
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
     */
    public function simAction(Structure $structure, Request $request)
    {
        $user = $this->getUser();
        $person_id = $request->get('person_id');
        $simulation = $this->testAdminTakeOver($user, $person_id);

        if (!$this->em->getRepository('App:Period')->getSimulationActive($structure)) {
            $this->session->getFlashBag()->add('error', 'Aucune session de simulation en cours actuellement. Repassez plus tard.');
            return $this->redirect($request->headers->get('referer'));
        }

        $last_period = $this->em->getRepository('App:Period')->getLast($structure);
        $department_table = $this->setRepartitionTable($structure, $last_period);
        $this->em->getRepository('App:Simulation')->doSimulation($department_table, $this->em, $last_period);

        $this->session->getFlashBag()->add('notice', 'Les données de la simulation ont été actualisées');

        return $this->redirect($this->generateUrl('app_dashboard_user', array('person_id' => $person_id, 'slug' => $structure->getSlug())));
    }

    /**
     * Construit la table des répartitions
     */
    private function setRepartitionTable(Structure $structure, Period $period)
    {
        $repartitions = $this->em->getRepository('App:Repartition')->getByPeriod($structure, $period);

        foreach ($repartitions as $repartition) {
            $department_table[$repartition->getDepartment()->getId()] = $repartition->getNumber();
            if ($repartition->getCluster() != null) {
                $department_table['cl_'.$repartition->getCluster()][] = $repartition->getDepartment()->getId();
            }
        }
    }

    /**
     * Affiche la liste des poste restants pour l'étudiant
     *
     * @Route("/{slug}/simulation/left", name="GSimul_SLeft", requirements={"slug" = "\w+"})
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
     * @Template()
     */
    public function listLeftPlacementsAction(Structure $structure, Request $request)
    {
        $user = $this->getUser();
        $person_id = $request->get('person_id');
        $simulation = $this->testAdminTakeOver($user, $person_id);

      $last_period = $this->em->getRepository('App:Period')->getLast($structure);
      $repartitions = $this->em->getRepository('App:Repartition')->getAvailable($structure, $last_period);
      $left = array();

      $sims = $this->em->getRepository('App:Simulation')->getDepartmentLeftForRank($simulation->getRank(), $last_period);
      foreach($sims as $sim) {
        foreach($sim->getDepartment()->getRepartitions() as $repartition) {
          if($cluster_name = $repartition->getCluster()) {
            foreach($this->em->getRepository('App:Repartition')->getByPeriodAndCluster($structure, $last_period, $cluster_name) as $other_repartition) {
              $left[$other_repartition->getDepartment()->getId()] = $sim->getExtra();
            }
          }
        }
        $left[$repartition->getDepartment()->getId()] = $sim->getExtra();
      }

      if ($person_id != null) {
        $simname = $this->em->getRepository('App:Simulation')->find($person_id)->getPerson();
      } else {
        $simname = null;
      }

      return array(
        'repartitions' => $repartitions,
        'left'         => $left,
        'person_id'        => $person_id,
        'simname'      => $simname,
        'structure'   => $structure,
      );
    }

    /**
     * Affiche la liste des simulations
     *
     * @Route("/{slug}/simulation/listSim", name="GSimul_SList", requirements={"slug" = "\w+"})
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
     * @Template()
     */
    public function listSimulationsAction(Structure $structure)
    {
        if (!$this->em->getRepository('App:Period')->getSimulationActive($structure)) {
            $this->session->getFlashBag()->add('error', 'Aucune session de simulation en cours actuellement. Repassez plus tard.');
            return $this->redirect($this->generateUrl('app_dashboard_user', array('person_id' => $person_id, 'slug' => $structure->getSlug())));
        }

      $simulations = $this->em->getRepository('App:Simulation')->getAll()->getResult();

      return array(
        'simulations' => $simulations,
        'structure'   => $structure,
      );
    }

    /**
     * Affiche la liste des simulations pour un department donné
     *
     * @Route("/{slug}/simulation/department/{id}", name="GSimul_SListDept", requirements={"slug" = "\w+"})
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
     * @Template("simulation/list_simulations.html.twig")
     */
    public function listSimulDeptAction(Structure $structure, Department $department)
    {
        if (!$this->em->getRepository('App:Period')->getSimulationActive($structure)) {
            $this->session->getFlashBag()->add('error', 'Aucune session de simulation en cours actuellement. Repassez plus tard.');
            return $this->redirect($this->generateUrl('app_dashboard_user', array('person_id' => $person_id, 'slug' => $structure->getSlug())));
        }

      $simulations = $this->em->getRepository('App:Simulation')->findByDepartment($department->getId());

      return array(
        'simulations' => $simulations,
        'structure'   => $structure,
      );
    }

    /**
     * Test for admin take over function
     *
     * @return simulation
     */
    private function testAdminTakeOver($user, $person_id)
    {
        if ($user->hasRole('ROLE_ADMIN') and $person_id) {
            $person = $this->em->getRepository('App:Person')->find($person_id);
            return $this->em->getRepository('App:Simulation')->getSimulation($person);
        } else {
            return $this->em->getRepository('App:Simulation')->getByUser($user);
        }
    }

    /**
     * @Route("/{slug}/simulation/list", name="GSimul_SAList", requirements={"slug" = "\w+"})
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
     * @Template()
     */
    public function listAction(Structure $structure)
    {
        $simulations_query = $this->em->getRepository('App:Simulation')->getAll();
        $simul_missing = $this->em->getRepository('App:Simulation')->countMissing();
        $simul_total = $this->em->getRepository('App:Simulation')->countTotal();

        return array(
            'simulations'   => $simulations_query->getResult(),
            'simul_missing' => $simul_missing,
            'simul_total'   => $simul_total,
            'structure'     => $structure,
        );
    }

    /**
     * Set simulation's rank up
     *
     * @Route("/{slug}/simulation/person/{id}/up", name="GSimul_SAUp", requirements={"id" = "\d+", "slug" = "\w+"})
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
     */
    public function setRankUpAction(Structure $structure, Simulation $simulation)
    {
        $rank = $simulation->getRank();

        if ($rank > 1) {
            $simulation_before = $this->em->getRepository('App:Simulation')->findOneByRank($rank - 1);

            $simulation_before->setRank($rank);
            $simulation->setRank($rank - 1);

            $this->em->persist($simulation);
            $this->em->persist($simulation_before);
            $this->em->flush();

            $this->session->getFlashBag()->add('notice', 'Étudiant ' . $simulation->getPerson() . ' déplacé au rang ' . $simulation->getRank() . '.');
            $this->session->getFlashBag()->add('notice', 'Étudiant ' . $simulation_before->getPerson() . ' déplacé au rang ' . $simulation_before->getRank() . '.');
        } else {
            $this->session->getFlashBag()->add('error', 'Étudiant ' . $simulation->getPerson() . ' est déjà le premier de la liste !');
        }

        return $this->redirect($this->generateUrl('GSimul_SAList', ['slug' => $structure->getSlug()]));
    }

    /**
     * Set simulation's rank down
     *
     * @Route("/{slug}/simulation/person/{id}/down", name="GSimul_SADown", requirements={"id" = "\d+", "slug" = "\w+"})
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
     */
    public function setRankDownAction(Structure $structure, Simulation $simulation)
    {
        $rank = $simulation->getRank();
        $simulation_total = $this->em->getRepository('App:Simulation')->countTotal();

        if ($rank < $simulation_total) {
            $simulation_after = $this->em->getRepository('App:Simulation')->findOneByRank($rank + 1);

            $simulation_after->setRank($rank);
            $simulation->setRank($rank + 1);

            $this->em->persist($simulation);
            $this->em->persist($simulation_after);
            $this->em->flush();

            $this->session->getFlashBag()->add('notice', 'Étudiant ' . $simulation->getPerson() . ' déplacé au rang ' . $simulation->getRank() . '.');
            $this->session->getFlashBag()->add('notice', 'Étudiant ' . $simulation_after->getPerson() . ' déplacé au rang ' . $simulation_after->getRank() . '.');
        } else {
            $this->session->getFlashBag()->add('error', 'Étudiant ' . $simulation->getPerson() . ' est déjà le dernier de la liste !');
        }

        return $this->redirect($this->generateUrl('GSimul_SAList', ['slug' => $structure->getSlug()]));
    }

    /**
     * @Route("/{slug}/simulation/repartition", name="GSimul_SALiveRepart", requirements={"slug" = "\w+"})
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
     * @Template()
     */
    public function liveRepartAction(Structure $structure, Request $request)
    {
        $simulations = $this->em->getRepository('App:Simulation')->getAll();
        $simul_missing = $this->em->getRepository('App:Simulation')->countMissing();
        $simul_total = $this->em->getRepository('App:Simulation')->countTotal();

        return array(
            'simulations'   => $simulations,
            'simul_missing' => $simul_missing,
            'simul_total'   => $simul_total,
            'structure'   => $structure,
        );
    }

    /**
     * @Route("/{slug}/simulation/live", name="GSimul_SALiveSimul", requirements={"slug" = "\w+"})
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
     */
    public function liveSimulAction(Structure $structure, Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $response = null;
            $id = $request->get('id');
            $simulation = $this->em->getRepository('App:Simulation')->find($id);
            if (!$simulation) {
                $response = new JsonResponse(array('message' => 'Error: Unknown entity.'), 412);
            }
            $form = $this->createForm(SimulationType::class, $simulation);

            if ($request->isMethod('POST')) {
                $form->bind($request);

                if ($form->isValid()) {
                    $simul = $form->getData();
                    $simul->setIsValidated(true);

                    $left = $this->em->getRepository('App:Simulation')->getNumberLeft($simul->getDepartment()->getId(), $simulation->getRank());
                    if (null === $left) {
                        $simul_period = $this->em->getRepository('App:SimulPeriod')->getLast()->getPeriod();
                        $repartition = $this->em->getRepository('App:Repartition')->getByPeriodAndDepartment($simul_period, $simul->getDepartment()->getId());
                        if (isset($repartition))
                            $extra = (int) $repartition->getNumber() - 1;
                        else
                            $extra = 0;
                    } else {
                        $extra = $left->getExtra() - 1;
                    }
                    $simul->setExtra($extra);

                    $this->em->persist($simul);
                    $this->em->flush();

                    $response = new JsonResponse(array(
                        'message'=> 'Success !',
                        'entity' => array(
                            'department'  => $simulation->getDepartment()->getHospital()->getName() . ' : ' . $simulation->getDepartment()->getName(),
                            'isExcess'    => $simulation->isExcess(),
                            'isValidated' => $simulation->isValidated(),
                            'isActive'    => $simulation->getActive(),
                        ),

                    ), 200);
                } else {
                    $response = new JsonResponse(array(
                        'message'    => 'Errors in the form !',
                    ), 412);
                }
            }

            if (!$response) {
                $response = new JsonResponse(array(
                    'message' => 'Use the form !',
                    'form'    => $this->renderView('App:SimulationAdmin:form.html.twig', array(
                        'entity' => $simulation,
                        'form'   => $form->createView(),
                    ))), 200)
                ;
            }
        }
        return $response;
    }

    /**
     * Affiche la liste des poste restants lors de la répartition
     *
     * @Route("/{slug}/simulation/live/left", name="GSimul_SALiveLeft", requirements={"slug" = "\w+"})
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
     * @Template()
     */
    public function liveLeftAction(Structure $structure, Request $request)
    {
      $last_period = $this->em->getRepository('App:Period')->getLast($structure);
      $sector = $this->em->getRepository('App:Sector')->find($request->get('sector', 0));
      if (!$sector)
          $sector = $this->em->getRepository('App:Sector')->getNext();
      $left = array();

      $sectors = $this->em->getRepository('App:Sector')->findAll();
      $repartitions = $this->em->getRepository('App:Repartition')->getAvailableForSector($structure, $last_period, $sector);
      $sims = $this->em->getRepository('App:Simulation')->getDepartmentLeftForSector($sector->getId(), $last_period);
      $left = array();

      foreach($sims as $sim) {
        $extra = $sim->getExtra();
        foreach($sim->getDepartment()->getRepartitions() as $repartition) {
          if($cluster_name = $repartition->getCluster()) {
            foreach($this->em->getRepository('App:Repartition')->getByPeriodAndCluster($structure, $last_period, $cluster_name) as $other_repartition) {
              $left[$other_repartition->getDepartment()->getId()] = $extra;
            }
          }
        }
        $left[$repartition->getDepartment()->getId()] = $extra;
      }

      return array(
        'repartitions' => $repartitions,
        'left'         => $left,
        'cur_sector'   => $sector,
        'sectors'      => $sectors,
        'structure'    => $structure,
      );
    }


    /**
     * @Route("/period/simul/{id}", name="GSimul_SAPeriod", requirements={"id" = "\d+"})
     * @Template()
     */
    public function periodAction(Period $period)
    {
        $simul_period = $this->em->getRepository('App:SimulPeriod')->findOneByPeriod($period->getId());

        if (!$simul_period) {
            $simul_period = new SimulPeriod();
            $simul_period->setPeriod($period);
        }

        $form = $this->createForm(SimulPeriodType::class, $simul_period);
        $form_handler = new SimulPeriodHandler($form, $request, $this->em, $period);

        if ($form_handler->process()) {
            $this->session->getFlashBag()->add('notice', 'Session de simulations du "' . $simul_period->getBegin()->format('d-m-Y') . '" au "' . $simul_period->getEnd()->format('d-m-Y') . '" modifiée.');
            return $this->redirect($this->generateUrl('GCore_PAPeriodIndex', ['slug' => $structure->getSlug()]));
        }

        return array(
            'period'      => $period,
            'period_form' => $form->createView(),
            'simul_period'=> $simul_period,
            'structure'   => $structure,
        );
    }

    /**
     * @Route("/period/simul/{id}/delete", name="GSimul_SAPeriodDelete", requirements={"id" = "\d+"})
     */
    public function deletePeriodAction(SimulPeriod $simul_period)
    {
      $this->em->remove($simul_period);
      $this->em->flush();

      $this->session->getFlashBag()->add('notice', 'Session de simulations du "' . $simul_period->getBegin()->format('d-m-Y') . '" au "' . $simul_period->getEnd()->format('d-m-Y') . '" supprimée.');

      return $this->redirect($this->generateUrl('GCore_PAPeriodIndex', ['slug' => $structure->getSlug()]));
    }

    /**
     * @Route("/{slug}/simulation/define", name="GSimul_SADefine", requirements={"slug" = "\w+"})
     * @Entity("structure", expr="repository.findOneBySlug(slug)")
     */
    public function defineAction(Structure $structure)
    {
        if($simulation = $this->em->getRepository('App:Simulation')->countTotal()) {
            $this->session->getFlashBag()->add('error', 'La table de simulation est déjà générée.');

                return $this->redirect($this->generateUrl('app_dashboard_admin', ['slug' => $structure->getSlug()]));
        } else {
            $persons = $this->em->getRepository('App:Person')->getRankingOrder();
            $count = $this->em->getRepository('App:Simulation')->setSimulationTable($persons, $this->em);

            if ($count) {
                $this->session->getFlashBag()->add('notice', $count . ' étudiants enregistrés dans la table de simulation.');
            } else{
                $this->session->getFlashBag()->add('error', 'Attention : Aucun étudiant enregistré dans la table de simulation.');
            }

            return $this->redirect($this->generateUrl('GSimul_SAList'));
        }
    }

    /**
     * @Route("/{stug}/simulation/define/import", name="GSimul_SAImport", requirements={"slug" = "\w+"})
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
     * @Template("person/import.html.twig")
     */
    public function importAction(Structure $structure, Request $request)
    {
        $this->em = $this->getDoctrine()->getManager();
        $error = 0;
        $form = $this->createFormBuilder()
            ->add('file', 'file', array(
                'label'    => 'Fichier',
                'required' => true,
            ))
            ->add('Envoyer', 'submit')
            ->getForm();

        $form->handleRequest($request);
        if ($form->isValid()) {
            $fileConstraint = new File();
            $fileConstraint->mimeTypesMessage = "Invalid mime type : ODS or XLS required.";
            $fileConstraint->mimeTypes = array(
                'application/vnd.oasis.opendocument.spreadsheet',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/octet-stream',
            );
            $errorList = $this->get('validator')->validateValue($form['file']->getData(), $fileConstraint);

            if(count($errorList) == 0) {
                $objPHPExcel = $this->get('phpexcel')->createPHPExcelObject($form['file']->getData())->setActiveSheetIndex();

                if ($person_rank = $this->em->getRepository('App:Simulation')->getLast())
                    $count = (int) $person_rank->getRank() + 1;
                else
                    $count = 2;
                $person_count = 0;
                $error = 0;

                while ($rank = $objPHPExcel->getCellByColumnAndRow(0, $count)) {
                    $name['last'] = strtolower($objPHPExcel->getCellByColumnAndRow(1, $count));
                    $name['alt'] = strtolower($objPHPExcel->getCellByColumnAndRow(2, $count));
                    $name['first'] = strtolower($objPHPExcel->getCellByColumnAndRow(3, $count));

                    if ($persons = $this->em->getRepository('App:Person')->searchExact($name))
                    {
                        if (count($persons) < 2) {
                            $simulation = new Simulation();
                            $simulation->setId($rank);
                            $simulation->setPerson($persons[0]);
                            $simulation->setRank($rank);
                            $simulation->setActive(true);
                            $this->em->persist($simulation);
                            $person_count++;
                        } else {
                            $error++;
                        }
                    } else {
                        $error++;
                    }
                    if (in_array($count, array(200, 400, 600, 800))) {
                        $this->em->flush();
                        $this->session->getFlashBag()->add('notice', $person_count . ' étudiants enregistrés dans la table de simulation.');
                        $this->session->getFlashBag()->add('error', $error . ' étudiants ont posé problème.');
                        $this->redirect('GSimul_SAImport');
                    }
                    $count++;
                }

                $this->session->getFlashBag()->add('notice', $person_count . ' étudiants enregistrés dans la table de simulation.');
                $this->session->getFlashBag()->add('error', $error . ' étudiants ont posé problème.');
                $this->redirect('GSimul_SAList');
            }
        }

        return array(
            'form'  => $form->createView(),
            'error' => $error,
            'structure'   => $structure,
        );
    }

    /**
     * @Route("/{slug}/simulation/purge", name="GSimul_SAPurge", requirements={"slug"="\w+"})
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
     */
    public function purgeAction(Structure $structure)
    {
        $sims = $this->em->getRepository('App:Simulation')->findAll();

        foreach ($sims as $sim) {
            $this->em->remove($sim);
        }

        $this->em->flush();

        $this->session->getFlashBag()->add('notice', "Les données de la simulation ont été supprimées.");

        return $this->redirect($this->generateUrl('app_dashboard_admin', ['slug' => $structure->getSlug()]));
    }

    /**
     * @Route("/{slug}/simulation/save", name="GSimul_SASave", requirements={"slug" = "\w+"})
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
     */
    public function saveAction(Structure $structure)
    {
        $this->em = $this->getDoctrine()->getManager();

        if ($this->em->getRepository('App:Period')->getSimulationActive($structure)) {
            $this->session->getFlashBag()->add('error', 'La simulation est toujours active ! Vous ne pourrez la valider qu\'une fois qu\'elle sera inactive. Aucune donnée n\'a été copiée.');

            return $this->redirect($this->generateUrl('GSimul_SAList', ['slug' => $structure->getSlug()]));
        }

        $sims = $this->em->getRepository('App:Simulation')->getAllValid();
        $simulPeriod = $this->em->getRepository('App:SimulPeriod')->getLastActive();
        if (!$simulPeriod) {
            $this->session->getFlashBag()->add('error', 'Il n\'y a aucune simulation antérieure retrouvée.');

            return $this->redirect($this->generateUrl('GSimul_SAList', ['slug' => $structure->getSlug()]));
        } else {
            $period = $simulPeriod->getPeriod();
        }

        $error = array('total' => 0, 'details' => '');
        foreach ($sims as $sim) {
            if ($current_repartition = $sim->getDepartment()->findRepartition($period)) {
                if($cluster_name = $current_repartition->getCluster()) {
                    $other_repartitions = $this->em->getRepository('App:Repartition')->getByPeriodAndCluster($period, $cluster_name);

                    foreach ($other_repartitions as $repartition) {
                        $placement = new Placement();
                        $placement->setPerson($sim->getPerson());
                        $placement->setRepartition($repartition);
                        $this->em->persist($placement);
                    }
                }
                $placement = new Placement();
                $placement->setPerson($sim->getPerson());
                $placement->setRepartition($current_repartition);
                $this->em->persist($placement);
            } else {
                $error['total']++;
                $error['details'] .= ' [' . $sim->getPerson() . '|' . $sim->getDepartment() . ':' . $sim->getExtra() . '] ';
            }
        }

        $this->em->flush();

        $this->session->getFlashBag()->add('notice', 'Les données de la simulation ont été copiées dans les stages.');
        if ($error['total'])
            $this->session->getFlashBag()->add('warning', 'Il y a eu ' . $error['total'] . ' erreurs d\'enregistrement.' . $error['details']);

        return $this->redirect($this->generateUrl('GSimul_SAPurge', ['slug' => $structure->getSlug()]));
    }

    /**
     * Affiche un tableau de SectorRule
     *
     * @Route("/{slug}/simulation/rule", name="GSimul_SARule", requirements={"slug" = "\w+"})
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
     * @Template()
     */
    public function ruleAction(Structure $structure)
    {
      $this->em = $this->getDoctrine()->getManager();
      $rules = $this->em->getRepository('App:SectorRule')->getAll();

      return array(
        'rules'     => $rules,
        'rule_form' => null,
        'structure'   => $structure,
      );
    }

    /**
     * Affiche un formulaire d'ajout de SectorRule
     *
     * @Route("/{slug}/simulation/rule/new", name="GSimul_SANewRule", requirements={"slug" = "\w+"})
     * @Entity("structure", expr="repository.findOneBy({'slug': slug})")
     * @Template("simulation/rule.html.twig")
     */
    public function newRuleAction(Structure $structure)
    {
      $this->em = $this->getDoctrine()->getManager();
      $rules = $this->em->getRepository('App:SectorRule')->getAll();

      $sector_rule = new SectorRule();
      $form = $this->createForm(SectorRuleType::class, $sector_rule);
      $form_handler = new SectorRuleHandler($form, $request, $this->em);

      if ($form_handler->process()) {
        $this->session->getFlashBag()->add('notice', 'Relation entre "' . $sector_rule->getSector()->getName() . '" et "' . $sector_rule->getGrade()->getName() . '" ajoutée.');

        return $this->redirect($this->generateUrl('GSimul_SARule', ['slug' => $structure->getSlug()]));
      }

      return array(
        'rules'     => $rules,
        'rule_form' => $form->createView(),
        'structure'   => $structure,
      );
    }

    /**
     * Supprime un SectorRule
     *
     * @Route("/{slug}/simulation/rule/{id}/d", name="GSimul_SADeleteRule", requirements={"id" = "\d+", "slug" = "\w+"})
     */
    public function deleteRuleAction(Structure $structure, $id)
    {
      $this->em = $this->getDoctrine()->getManager();
      $rule = $this->em->getRepository('App:SectorRule')->find($id);

      if (!$rule)
        throw $this->createNotFoundException('Unable to find sector_rule entity.');

      $this->em->remove($rule);
      $this->em->flush();

      $this->session->getFlashBag()->add('notice', 'Règle de simulation pour "' . $rule . '" supprimée.');

      return $this->redirect($this->generateUrl('GSimul_SARule', ['slug' => $structure->getSlug()]));
    }
}
