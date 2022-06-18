<?php

/**
 * This file is part of GESSEH project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2013-2020 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\Structure;

/**
 * PlacementRepository
 */
class PlacementRepository extends EntityRepository
{
  public function getBaseQuery()
  {
      return $this
          ->createQueryBuilder('p')
          ->join('p.person', 's')
          ->join('p.repartition', 'r')
          ->join('r.period', 'q')
          ->join('r.department', 'd')
          ->join('s.user', 'u')
          ->join('d.hospital', 'h')
          ->join('d.accreditations', 'a')
          ->join('a.sector', 't')
          ->addSelect('r')
          ->addSelect('q')
          ->addSelect('d')
          ->addSelect('h')
          ->addSelect('a')
          ->addSelect('t')
      ;
  }

  public function getByUsername($user, $id = null)
  {
    $query = $this->getBaseQuery();
    $query->where('u.username = :user')
          ->setParameter('user', $user)
          ->andWhere('a.begin <= q.begin')
          ->andWhere('a.end >= q.end')
          ->addOrderBy('q.begin', 'desc')
          ->addOrderBy('h.name', 'asc')
          ->addOrderBy('d.name', 'asc');

    if ($id) {
        $query->andWhere('p.id = :id')
            ->setParameter('id', $id);

        return $query->getQuery()->getOneOrNullResult();
    }

    return $query->getQuery()->getResult();
  }

    public function getByPerson($person_id)
    {
        $query = $this->getBaseQuery();
        $query->where('s.id = :person_id')
            ->setParameter('person_id', $person_id)
            ->andWhere('a.begin <= q.begin')
            ->andWhere('a.end >= q.end')
            ->addOrderBy('q.begin', 'desc')
            ->addOrderBy('h.name', 'asc')
            ->addOrderBy('d.name', 'asc')
        ;

        return $query->getQuery()
            ->getResult()
        ;
    }

  public function getByUsernameAndDepartment($user, $id = null)
  {
    $query = $this->getBaseQuery();
    $query->where('u.username = :user')
          ->setParameter('user', $user)
          ->andWhere('d.id = :id')
          ->setParameter('id', $id)
          ->addOrderBy('q.begin', 'desc')
          ->addOrderBy('h.name', 'asc')
          ->addOrderBy('d.name', 'asc')
    ;

    return $query->getQuery()
                 ->getResult()
    ;
  }

  public function getAll(Structure $structure, $limit = null)
  {
      $query = $this
          ->getBaseQuery()
          ->where('h.structure = :structure')
          ->setParameter('structure', $structure)
          ->addOrderBy('q.begin', 'desc')
          ->addOrderBy('s.surname', 'asc')
          ->addOrderBy('s.name', 'asc')
          ->addOrderBy('h.name', 'asc')
          ->addOrderBy('d.name', 'asc')
          ->addSelect('s')
      ;

    if (null != $limit and preg_match('/^[p,q,s,t,h,d].id$/', $limit['type'])) {
      $query->andWhere($limit['type'] . ' = :value')
               ->setParameter('value', $limit['value']);
    }

    return $query
        ->getQuery()
        ->getResult()
    ;
  }

  public function getCountByPersonWithoutCurrentPeriod($person, $current_period = null)
  {
      $query = $this->createQueryBuilder('p')
                    ->select('COUNT(p)')
                    ->where('p.person = :person')
                    ->setParameter('person', $person)
      ;

    if ($current_period != null) {
        $query->join('p.repartition', 'r')
              ->andWhere('r.period != :current_period')
              ->setParameter('current_period', $current_period)
        ;
    }

      return $query->getQuery()
                   ->getSingleScalarResult()
      ;
  }

    public function getByPersonAndDepartment($person_id, $department_id)
    {
        $query = $this->createQueryBuilder('p')
            ->join('p.person', 's')
            ->join('p.repartition', 'r')
            ->join('r.period', 'q')
            ->join('r.department', 'd')
            ->where('s.id = :person_id')
            ->andWhere('d.id = :department_id')
            ->setParameter('person_id', $person_id)
            ->setParameter('department_id', $department_id)
        ;

        return $query->getQuery()
            ->getResult();
    }

    public function getByRepartition($repartition)
    {
        return $this->getBaseQuery()
            ->where('r.id = :repartition_id')
            ->setParameter('repartition_id', $repartition->getId())
            ->addOrderBy('s.surname', 'asc')
            ->addOrderBy('s.name', 'asc')
            ->getQuery()
            ->getResult()
        ;
    }
}
