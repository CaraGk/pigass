<?php

/**
 * This file is part of PIGASS project
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
 * PersonRepository
 */
class PersonRepository extends EntityRepository
{
  public function getBaseQuery()
  {
    return $this->createQueryBuilder('s')
                ->join('s.user', 'u')
                ->addSelect('u')
    ;
  }

  public function getById($id)
  {
    $query = $this->getBaseQuery();
    $query->where('s.id = :id')
          ->setParameter('id', $id);

    return $query->getQuery()
                 ->getSingleResult();
  }

  public function getAll(Structure $structure, $search = null)
  {
    $query = $this->getBaseQuery();
    $query->addOrderBy('s.surname', 'asc')
        ->where('s.structure = :structure')
        ->setParameter('structure', $structure)
    ;

    if ($search != null) {
        $query->andWhere('s.surname like :search OR s.name like :search OR u.email like :search')
              ->setParameter('search', '%'.$search.'%');
    }

    return $query->getQuery();
  }

    public function countAll(Structure $structure, $active = true, $search = null)
    {
        $query=$this->createQueryBuilder('s')
            ->select('COUNT(s)')
            ->join('s.user', 'u')
            ->where('s.structure = :structure')
            ->setParameter('structure', $structure)
        ;

        if ($search != null) {
            $query->andWhere('s.surname like :search OR s.name like :search OR u.email like :search')
                  ->setParameter('search', '%'.$search.'%');
        }

        return $query->getQuery()
            ->getSingleScalarResult();
    }

  public function getByUsername($username)
  {
    $query = $this->getBaseQuery();
    $query->where('u.username = :username')
            ->setParameter('username', $username);

    return $query->getQuery()
                 ->getSingleResult();
  }

    public function getByUser($user)
    {
        $query = $this->getBaseQuery();
        $query->where('u.id = :userid')
            ->setParameter('userid', $user->getId())
        ;

        return $query->getQuery()
            ->getSingleResult()
        ;
    }

    public function getMailsByStructure($grade_id)
    {
        $query = $this->getBaseQuery();
        $query->where('s.grade = :grade_id')
            ->setParameter('grade_id', $grade_id);

        $result= $query->getQuery()->getResult();
        $list = "";

        foreach ($result as $person) {
            $list .= $person->getUser()->getEmail() . ", ";
        }

        $list = substr($list, 0, -2);

        return $list;
    }

    public function searchExact(array $name)
    {
        $query = $this->createQueryBuilder('s')
            ->where('(s.surname LIKE :surname1 OR s.surname LIKE :surname2 OR s.surname LIKE :surname3 OR s.surname LIKE :surname4)')
            ->setParameter('surname1', '%'.$name['last'].'%')
            ->setParameter('surname2', '%'.$name['alt'].'%')
            ->setParameter('surname3', '%'.$this->stripAccents($name['last']).'%')
            ->setParameter('surname4', '%'.$this->stripAccents($name['alt']).'%')
            ->andWhere('(s.name LIKE :name1 OR s.name LIKE :name2)')
            ->setParameter('name1', '%'.$name['first'].'%')
            ->setParameter('name2', '%'.$this->stripAccents($name['first']).'%')
        ;

        return $query->getQuery()
                     ->getResult()
        ;
    }

    private function stripAccents($str, $charset='utf-8')
    {
        $str = htmlentities($str, ENT_NOQUOTES, $charset);
        $str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'
        $str = preg_replace('#&[^;]+;#', '', $str); // supprime les autres caractères
        $str = str_replace('-', ' ', $str);
        return $str;
    }

    public function getByRole($role, Structure $structure = null)
    {
        $query = $this->getBaseQuery()
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%' . $role . '%')
        ;

        if ($structure)
            $query->andWhere('s.structure = :structure_id')
                ->setParameter('structure_id', $structure->getId())
            ;

        return $query
            ->getQuery()
            ->getResult()
        ;
    }

    public function getBaseWithGradesQuery() {
        return $this->getBaseQuery()
            ->join('s.grade', 'g')
            ->addSelect('g')
        ;
    }

    public function setGradeUp($current_grade, $next_grade)
    {
        $query = $this->getEntityManager()
                  ->createQuery('UPDATE App:Person s SET s.grade = :next_grade WHERE s.grade = :current_grade')
                  ->setParameters(array(
                      'current_grade' => $current_grade,
                      'next_grade' => $next_grade,
                  ));

    return $query->getResult();
  }

  public function getRankingOrder()
  {
    $query = $this->getBaseWithGradesQuery();
    $query->where('u.enabled = true')
          ->andWhere('g.isActive = true')
          ->addOrderBy('g.rank', 'desc')
          ->addOrderBy('s.graduate', 'asc')
          ->addOrderBy('s.ranking', 'asc');

    return $query->getQuery()->getResult();
  }

  public function getWithPlacementNotIn($notInList)
  {
      $query = $this->getBaseWithGradesQuery();
      $query->join('s.placements', 'p')
          ->addSelect('p')
          ->where('g.isActive = true')
          ->andWhere('p.id NOT IN (' . implode(',', $notInList) . ')');

      return $query->getQuery()->getResult();
  }

    public function getMailsByGrade($grade_id)
    {
        $query = $this->getBaseWithGradesQuery();
        $query->where('s.grade = :grade_id')
            ->setParameter('grade_id', $grade_id);

        $result= $query->getQuery()->getResult();
        $list = "";

        foreach ($result as $person) {
            $list .= $person->getUser()->getEmail() . ", ";
        }

        $list = substr($list, 0, -2);

        return $list;
    }

    public function getSamePlacement($period_id, $department_id)
    {
        $query = $this->getBaseWithGradesQuery();
        $query->join('s.placements', 'p')
              ->join('p.repartition', 'r')
              ->where('r.period = :period_id')
              ->setParameter('period_id', $period_id)
              ->andWhere('r.department = :department_id')
              ->setParameter('department_id', $department_id)
        ;

        return $query->getQuery()
                     ->getResult()
        ;
    }
}
