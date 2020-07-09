<?php

/**
 * This file is part of GESSEH project
 *
 * @author: Pierre-François ANGRAND <gesseh@medlibre.fr>
 * @copyright: Copyright 2013-2017 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\Structure,
    App\Entity\Person,
    App\Entity\Period;

/**
 * EvaluationRepository
 */
class EvaluationRepository extends EntityRepository
{
    /*
     * Generic query with joins
     *
     * @return QueryBuilder
     */
    public function getBaseQuery(Structure $structure)
    {
        return $this->createQueryBuilder('e')
            ->join('e.placement', 'p')
            ->join('p.repartition', 'r')
            ->join('r.department', 'd')
            ->join('d.hospital', 'h')
            ->join('e.evalCriteria', 'c')
            ->where('h.structure = :structure')
            ->setParameter('structure', $structure)
        ;
    }

    /**
     * Récupère les évaluations d'un terrain de stage
     *
     * @return query
     */
    public function getByDepartmentQuery(Structure $structure, $id, $limit = null)
    {
        $query = $this->getBaseQuery($structure);
        $query->join('r.period', 'q')
            ->andWhere('d.id = :id')
            ->setParameter('id', $id)
            ->andWhere('not(c.moderate = true and e.validated = false)')
            ->addOrderBy('c.rank', 'asc')
            ->addOrderBy('q.begin', 'asc')
        ;

        if ($limit != null) {
            if ($limit['date']) {
                $query->andWhere('e.created_at > :date')
                      ->setParameter('date', $limit['date'])
                ;
            }
            if ($limit['role']) {
                $query->andWhere('c.private = false');
            }
        }

        return $query;
    }

    /**
     * Met en forme les évaluations d'un department
     *
     * @return array
     */
    public function getByDepartment(Structure $structure, $id, $limit = null)
    {
        $query = $this->getByDepartmentQuery($structure, $id, $limit);
        $query->addSelect('q')
            ->addSelect('p')
            ->addSelect('r')
            ->addSelect('d')
            ->addSelect('c')
        ;
        $results = $query->getQuery()
                         ->getResult()
        ;
        $calc = array();
        $crits = array();

        foreach ($results as $result) {
            $criteria = $result->getEvalCriteria();
            $period_id = $result->getPlacement()
                                ->getRepartition()
                                ->getPeriod()
                                ->getId()
            ;
            $value = $result->getValue();
            $criteria_id = $criteria->getId();

            if (!isset($calc[$criteria_id]['name'][0])) {
                $calc[$criteria_id]['total'][0] = 0;
                $calc[$criteria_id]['name'] = $criteria->getName();
                $calc[$criteria_id]['type'] = $criteria->getType();
            }
            if (!isset($calc[$criteria_id]['total'][$period_id])) {
                $calc[$criteria_id]['total'][$period_id] = 0;
            }

            $calc[$criteria_id]['total'][0] ++;
            $calc[$criteria_id]['total'][$period_id] ++;

            if ($criteria->getType() == 2) {
                $calc[$criteria_id]['text'][] = $value;

            } elseif ($criteria->getType() == 1 or $criteria->getType() == 4 or $criteria->getType() == 7) {
                if (!isset($calc[$criteria_id]['count'][0])) {
                    $calc[$criteria_id]['count'][0] = 0;
                }
                if (!isset($calc[$criteria_id]['count'][$period_id])) {
                    $calc[$criteria_id]['count'][$period_id] = 0;
                }

                $calc[$criteria_id]['count'][0] += (int) $value;
                $calc[$criteria_id]['mean'][0] = round($calc[$criteria_id]['count'][0] / $calc[$criteria_id]['total'][0], 1);
                $calc[$criteria_id]['count'][$period_id] += (int) $value;
                $calc[$criteria_id]['mean'][$period_id] = round($calc[$criteria_id]['count'][$period_id] / $calc[$criteria_id]['total'][$period_id], 1);
            } elseif ($criteria->getType() == 3 or $criteria->getType() == 5 or $criteria->getType() == 6) {
                if (!in_array($criteria, $crits)) {
                    $crits[] = $criteria;
                }
                if (!isset($calc[$criteria_id]['count'][0][$value])) {
                    $calc[$criteria_id]['count'][0][$value] = 0;
                }
                if (!isset($calc[$criteria_id]['count'][$period_id][$value])) {
                    $calc[$criteria_id]['count'][$period_id][$value] = 0;
                }
                $calc[$criteria_id]['count'][0][$value] ++;
                $calc[$criteria_id]['count'][$period_id][$value] ++;
            }
        }

        foreach ($crits as $criteria) {
            if ($criteria->getType() == 3) {
                foreach (explode('|', $criteria->getMore()) as $item) {
                    if (isset($calc[$criteria->getId()]['count'][0][$item])) {
                        $divisor = max($calc[$criteria->getId()]['count'][0]) - min($calc[$criteria->getId()]['count'][0]);
                        if ($divisor <= 0) {
                            $divisor = 1;
                        }
                        $calc[$criteria->getId()]['size'][0][$item] = 0.5 + round(($calc[$criteria->getId()]['count'][0][$item] - min($calc[$criteria->getId()]['count'][0])) * (2 - 0.5) / $divisor, 1);
                    }
                }
            } elseif ($criteria->getType() == 5 or $criteria->getType() == 6) {
                $max = 1;
                if ($criteria->getMore()) {
                    $explode = explode('|', $criteria->getMore());
                    $max = (int) $explode[0];
                }
                asort($calc[$criteria->getId()]['count'][0]);
                $calc[$criteria->getId()]['max'] = $max;
            }
        }

        return $calc;
    }

    /**
     * Compte le nombre d'évaluation pour un department
     *
     * return int
     */
    public function countByDepartment(Structure $structure, $id, $limit = null)
    {
        $query = $this->getByDepartmentQuery($structure, $id, $limit);
        $query->select('COUNT(DISTINCT p.id)');

        return $query->getQuery()
                     ->getSingleScalarResult()
        ;
    }

  public function getEvaluatedList(Structure $structure, $type = 'array', $person = null)
  {
    $query = $this->createQueryBuilder('e')
        ->join('e.placement', 'p')
        ->join('p.person', 's')
        ->addSelect('p')
        ->where('s.structure = :structure')
        ->setParameter('structure', $structure)
    ;

    if ($person) {
        $query
            ->andWhere('s.id = :person_id')
            ->setParameter('person_id', $person->getId());
    }

    $results = $query->getQuery()->getResult();
    $list = array();

    foreach ($results as $result) {
      array_push($list, $result->getPlacement()->getId());
    }

    if ($type = 'array')
      return $list;
    elseif ($type = 'list')
      return implode(',', $list);
    else
      return null;
  }

    public function getToModerate(Structure $structure, $eval_id = null)
    {
        $query = $this->getBaseQuery($structure);
        $query->andWhere('e.validated = false')
              ->andWhere('e.moderated = false')
              ->addOrderBy('e.created_at', 'asc')
              ;

        if ($eval_id){
            $query->andWhere('e.id = :eval_id')
                  ->setParameter('eval_id', $eval_id)
            ;
            return $query->getQuery()->getOneOrNullResult();
        } else {
            return $query->getQuery();
        }
    }

    public function personHasNonEvaluated(Structure $structure, Person $person, Period $current_period, $count_placements)
    {
        $query = $this->getBaseQuery($structure);
        $query->select('COUNT(DISTINCT p.id)')
              ->andWhere('p.person = :person')
              ->setParameter('person', $person);

        if ($current_period != null) {
            $query->andWhere('r.period != :current_period')
                  ->setParameter('current_period', $current_period);
        }

        $count_evaluations = $query->getQuery()->getSingleScalarResult();

        if($count_evaluations < $count_placements)

            return true;
        else
            return false;
    }

    /**
     * Récupère les évaluations d'un étudiant pour un terrain de stage
     *
     * @return ArrayCollection
     */
    public function getByPlacement(Structure $structure, $id, $limit = null)
    {
        $query = $this->getBaseQuery($structure);
        $query->join('r.period', 'q')
            ->andWhere('p.id = :id')
            ->setParameter('id', $id)
            ->addOrderBy('c.rank', 'asc')
            ->addOrderBy('q.begin', 'asc')
        ;

        if ($limit != null) {
            if ($limit['role']) {
                $query->andWhere('c.private = false');
            }
        }

        return $query->getQuery()
                     ->getResult()
        ;
    }

    /**
     * Compte l'ensemble des évaluations d'une structure
     *
     * @return integer
     */
    public function countAll(Structure $structure, Array $options = ['toModerate' => false])
    {
        $query = $this->getBaseQuery($structure)
            ->select('COUNT(DISTINCT p)')
        ;

        if (isset($options['toModerate']) and $options['toModerate']) {
            $query->andWhere('e.validated = false')
                ->andWhere('e.moderated = false')
            ;
        }

        return $query
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
