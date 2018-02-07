<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2015-2018 Pierre-François Angrand
 * @license: GPLv3
 * See LICENSE file or http://www.gnu.org/licenses/gpl.html
 */

namespace Pigass\UserBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Pigass\UserBundle\Entity\Person;

/**
 * MembershipRepository
 */
class MembershipRepository extends EntityRepository
{
    public function getLastForPerson(Person $person)
    {
        $query = $this->createQueryBuilder('m');
        $query->join('m.person', 'p')
            ->where('p.id = :person')
            ->setParameter(':person', $person->getId())
            ->orderBy('m.id', 'desc')
            ->setMaxResults(1)
        ;

        return $query
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function getCurrentForPerson(Person $person, $payed = false)
    {
        $query = $this->createQueryBuilder('m');
        $query->where('m.person = :person')
            ->setParameter('person', $person->getId())
            ->andWhere('m.expiredOn > :now')
            ->setParameter('now', new \DateTime('now'))
            ->orderBy('m.expiredOn', 'desc')
            ->setMaxResults(1)
            ->join('m.structure', 't')
            ->addSelect('t')
        ;

        if ($payed)
            $query->andWhere('m.payedOn is not NULL');

        return $query->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function getAllByStructureQuery($slug, $filter = null, $anticipated = null)
    {
        $query = $this->createQueryBuilder('m')
            ->join('m.person', 's')
            ->addSelect('s')
            ->join('s.user', 'u')
            ->addSelect('u')
            ->join('m.structure', 't')
            ->addSelect('t')
            ->where('t.slug = :slug')
            ->setParameter('slug', $slug)
            ->orderBy('s.name', 'asc');

        if (isset($filter['valid'])) {
            if ($filter['valid'])
                $query->andWhere('m.payedOn is not NULL');
            elseif ($filter['valid'] != null)
                $query->andWhere('m.payedOn is NULL');
        }

        if (isset($filter['ending']) and $anticipated) {
            if ($filter['ending'] == true) {
                $query->andWhere('m.expiredOn < :anticipated')
                    ->setParameter('anticipated', $anticipated)
                ;
            } elseif($filter['ending'] == false) {
                $query->andWhere('m.expiredOn > :anticipated')
                    ->setParameter('anticipated', $anticipated)
                ;
            }
        }

        if (isset($filter['fee']) and $filter['fee']) {
            $query->andWhere('m.fee = :fee')
                ->setParameter('fee', $filter['fee'])
            ;
        }

        if (isset($filter['questions']) and $filter['questions']) {
            foreach ($filter['questions'] as $question_id => $value) {
                $query->join('m.infos', 'i')
                    ->join('i.question', 'q')
                    ->andWhere('q.id = :question_id')
                    ->setParameter('question_id', $question_id)
                    ->andWhere('i.value = :info_value')
                    ->setParameter('info_value', $value)
                ;
            }
        }

        if (isset($filter['search']) and $filter['search']) {
            $query
                ->andWhere('s.surname like :search OR s.name like :search OR u.email like :search')
                ->setParameter('search', '%' . $filter['search'] . '%')
                ->addOrderBy('m.expiredOn', 'desc')
            ;
        }

        return $query;
    }

    public function getAllByStructure($slug, $filter = null, $anticipated = null)
    {
        $query = $this->getAllByStructureQuery($slug, $filter, $anticipated);

        return $query
            ->getQuery()
            ->getResult()
        ;
    }

    public function getCurrentByStructure($slug, $filter = null, $anticipated = null)
    {
        $query = $this->getAllByStructureQuery($slug, $filter, $anticipated);
        $query
            ->andWhere('m.expiredOn > :now')
            ->setParameter('now', new \DateTime('now'))
        ;

        return $query
            ->getQuery()
            ->getResult()
        ;
    }

    public function getCurrentByStructureWithInfos($slug)
    {
        $query = $this->createQueryBuilder('m')
            ->join('m.person', 's')
            ->addSelect('s')
            ->join('s.user', 'u')
            ->addSelect('u')
            ->join('m.structure', 't')
            ->addSelect('t')
//            ->join('m.infos', 'i')
//            ->addSelect('i')
//            ->join('i.question', 'q')
//            ->addSelect('q')
            ->where('t.slug = :slug')
            ->setParameter('slug', $slug)
            ->andWhere('m.expiredOn > :now')
            ->setParameter('now', new \DateTime('now'))
            ->andWhere('m.payedOn is not NULL')
            ->addOrderBy('s.surname', 'asc')
            ->addOrderBy('s.name', 'asc')
        ;

        return $query->getQuery()->getResult();
    }

    public function getCurrentForPersonArray()
    {
        $query = $this->createQueryBuilder('m')
                      ->join('m.person', 's')
                      ->where('m.expiredOn > :now')
                      ->setParameter('now', new \DateTime('now'))
                      ->andWhere('m.payedOn is not NULL')
                      ->groupBy('s.id')
                      ->select('s.id')
        ;

        return $query->getQuery()
                     ->getArrayResult()
        ;
    }

    public function getAll()
    {
        $query = $this->createQueryBuilder('m')
            ->join('m.structure', 's')
            ->addSelect('s')
        ;

        return $query->getQuery()
            ->getResult()
        ;
    }
}
