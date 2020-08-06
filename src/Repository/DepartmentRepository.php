<?php

/**
 * This file is part of GESSEH project
 *
 * @author: Pierre-François ANGRAND <gesseh@medlibre.fr>
 * @copyright: Copyright 2013-2016 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\Structure;

/**
 * DepartmentRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class DepartmentRepository extends EntityRepository
{
  public function getBaseQuery()
  {
    return $this->createQueryBuilder('d')
                ->join('d.hospital', 'h')
                ->join('d.accreditations', 'a')
                ->join('a.sector', 's')
                ->addSelect('h')
                ->addSelect('a')
                ->addSelect('s')
    ;
  }

  public function getBaseQueryWithRepartitions()
  {
    $query = $this->getBaseQuery();

    return $query->join('d.repartitions', 'r')
                 ->addSelect('r');
  }

  /**
   * Get one department with joinable tables from id
   *
   * @return QueryResult
   */
  public function getById($id)
  {
    $query = $this->getBaseQuery();
    $query->where('d.id = :id')
          ->setParameter('id', $id);

    return $query->getQuery()
                 ->getSingleResult();
  }

  /**
   * Get next department for maintenance purpose
   *
   * @return Department
   */
    public function getNext($id = 0)
    {
        $query = $this->getBaseQuery();
        $query->where('d.id > :id')
              ->setParameter('id', $id)
              ->setMaxResults(1)
        ;

        return $query->getQuery()
                     ->getOneOrNullResult()
        ;
    }

  public function getByPerson($person_id)
  {
    $query = $this->getBaseQueryWithRepartitions();
    $query->join('r.placements', 'p')
          ->join('p.person', 't')
          ->where('t.id = :person_id')
          ->setParameter('person_id', $person_id);

    return $query->getQuery()
                 ->getResult()
    ;
  }

  public function getBySectorForPeriod($sector_id, $period_id)
  {
    $query = $this->getBaseQuery();
    $query
        ->join('d.repartitions', 'r')
        ->join('r.period', 'p')
        ->addSelect('r')
        ->where('a.revoked = false')
        ->andWhere('s.id = :sector_id')
        ->setParameter('sector_id', $sector_id)
        ->andWhere('p.id = :period_id')
        ->setParameter('period_id', $period_id)
    ;

    return $query->getQuery()
                 ->getResult()
    ;
  }

  public function getAll(array $orderBy = array('h' => 'asc', 's' => 'asc'))
  {
    $query = $this->getBaseQuery();
    foreach ($orderBy as $col => $order) {
      $query->addOrderBy($col . '.name', $order);
    }

    return $query->getQuery()
                 ->getResult();
  }

    public function getAvailableQuery()
    {
        $query = $this->getBaseQueryWithRepartitions();
        $query
            ->where('r.number > 0')
            ->andWhere('a.revoked = false')
            ->addOrderBy('h.name', 'asc')
            ->addOrderBy('d.name', 'asc')
        ;
        return $query;
    }

    public function getAvailable()
    {
        $query = $this->getAvailableQuery();

        return $query->getQuery()
                     ->getResult()
        ;
    }

    public function getAdaptedUserList($rules)
    {
        $query = $this->getAvailableQuery();

        if ($rules['department']['NOT'])
            $query->andWhere('d.id NOT IN (' . implode(',', $rules['department']['NOT']) . ')');
        if ($rules['sector']['NOT'])
            $query->andWhere('a.sector NOT IN (' . implode(',', $rules['sector']['NOT']) . ')');
        if ($rules['department']['IN'])
            $query->andWhere('d.id IN (' . implode(',', $rules['department']['IN']) . ')');

        return $query;
    }

    public function getByNameAndHospital($department_name, $hospital_name)
    {
        $query = $this->createQueryBuilder('d')
            ->join('d.hospital', 'h')
            ->where('LOWER(d.name) LIKE LOWER(:department_name)')
            ->andWhere('h.name = :hospital_name')
            ->setParameter('department_name', '%' . $department_name . '%')
            ->setParameter('hospital_name', $hospital_name)
            ->setMaxResults(1)
        ;

        return $query->getQuery()
            ->getOneOrNullResult();
    }

    public function getAllInArray()
    {
        return $this->createQueryBuilder('d')
            ->getQuery()
            ->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY)
        ;
    }
    public function countAll(Structure $structure)
    {
        return $this->createQueryBuilder('d')
            ->join('d.hospital', 'h')
            ->select('COUNT(d.id)')
            ->where('h.structure = :structure')
            ->setParameter('structure', $structure)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
