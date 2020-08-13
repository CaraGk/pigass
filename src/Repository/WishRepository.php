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

/**
 * WishRepository
 */
class WishRepository extends EntityRepository
{
  public function getWishPersonQuery($person_id)
  {
    return $this->createQueryBuilder('w')
                ->join('w.simulation', 't')
                ->where('t.person = :person')
                  ->setParameter('person', $person_id);
  }

  public function getWishQuery()
  {
      return $this
          ->createQueryBuilder('w')
          ->join('w.simulation', 't')
          ->join('w.department', 'd')
          ->join('d.hospital', 'h')
          ->join('d.accreditations', 'a')
          ->join('a.sector', 'u')
          ->where('a.revoked = false')
          ->addSelect('d')
          ->addSelect('h')
          ->addSelect('t')
          ->addSelect('a')
          ->addSelect('u')
      ;
  }

  public function getByPerson($person_id, $period_id)
  {
    $query = $this->getWishQuery();
    $query->andWhere('t.person = :person')
          ->setParameter('person', $person_id)
          ->join('d.repartitions', 'r')
          ->join('r.period', 'p')
          ->addSelect('r')
          ->andWhere('r.period = :period_id')
          ->setParameter('period_id', $period_id)
          ->addOrderBy('w.rank', 'asc');

    return $query->getQuery()->getResult();
  }

  public function findByUsername($username)
  {
    $query = $this->getWishQuery();
    $query->join('t.person', 's')
          ->join('s.user', 'v')
          ->andWhere('v.username = :username')
            ->setParameter('username', $username);

    return $query->getQuery()->getResult();
  }

  public function getPersonWishList($simulation_id)
  {
      $query = $this
          ->createQueryBuilder('w')
          ->join('w.department', 'd')
          ->join('d.accreditations', 'a')
          ->join('a.sector', 's')
          ->where('w.simulation = :simulation_id')
          ->setParameter('simulation_id', $simulation_id)
          ->andWhere('a.revoked = false')
          ->addSelect('d')
          ->addSelect('a')
          ->addSelect('s')
      ;

    return $query->getQuery()->getResult();
  }

  public function findByPersonAndRank($person_id, $rank, $period)
  {
    $query = $this->getWishPersonQuery($person_id);
    $query->join('w.department', 'd')
          ->addSelect('d')
          ->andWhere('w.rank = :rank')
          ->setParameter('rank', $rank);
    $wish = $query->getQuery()->getSingleResult();

    if ($current_repartition = $wish->getDepartment()->findRepartition($period)) {
        if($cluster_name = $current_repartition->getCluster()) {
            $query = $this->getWishQuery();
            $query->andWhere('t.person = :person_id')
                  ->setParameter('person_id', $person_id)
                  ->andWhere('r.cluster = :cluster')
                  ->setParameter('cluster', $cluster_name);

            return $query->getQuery()
                         ->getResult();
        } else {
            return array($wish);
        }
    }
  }

  public function findByPersonAndId($person_id, $id)
  {
    $query = $this->getWishPersonQuery($person_id);
    $query->andWhere('w.id = :id')
            ->setParameter('id', $id);

    return $query->getQuery()->getSingleResult();
  }

  public function findByRankAfter($person_id, $rank)
  {
    $query = $this->getWishPersonQuery($person_id);
    $query->andWhere('w.rank > :rank')
            ->setParameter('rank', $rank)
          ->addOrderBy('w.rank', 'asc');

    return $query->getQuery()->getResult();
  }

  public function getMaxRank($person_id)
  {
    $query = $this->getWishPersonQuery($person_id)
                  ->select('COUNT(w.id)');

    return $query->getQuery()->getSingleScalarResult();
  }

  public function getWishCluster($person_id, $wish_id, $period)
  {
      $query = $this->getWishQuery();
      $query->andWhere('w.id = :wish_id')
            ->setParameter('wish_id', $wish_id)
            ->andWhere('t.person = :person_id')
            ->setParameter('person_id', $person_id);
      $wish = $query->getQuery()->getSingleResult();

      if($current_repartition = $wish->getDepartment()->findRepartition($period)) {
          if($cluster_name = $current_repartition->getCluster()) {
              $query = $this->getWishQuery();
              $query->andWhere('t.person = :person_id')
                    ->setParameter('person_id', $person_id)
                    ->andWhere('r.cluster = :cluster')
                    ->setParameter('cluster', $cluster_name())
              ;

              return $query->getQuery()
                           ->getResult()
              ;
          } else {
              return array($wish);
          }
      }
  }
}
