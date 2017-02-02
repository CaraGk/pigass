<?php

/**
 * This file is part of PIGASS project
 *
 * @author: Pierre-François ANGRAND <pigass@medlibre.fr>
 * @copyright: Copyright 2015-2016 Pierre-François Angrand
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
    public function getLastByUsername($username)
    {
        $query = $this->createQueryBuilder('m');
        $query->join('m.person', 's')
            ->join('s.user', 'u')
            ->addSelect('s')
            ->addSelect('u')
            ->where('u.username = :username')
            ->setParameter('username', $username)
            ->orderBy('m.id', 'desc')
            ->setMaxResults(1)
        ;

        return $query->getQuery()->getSingleResult();
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

    public function getCurrentByStructure($slug, $filter = null)
    {
        $query = $this->createQueryBuilder('m')
            ->join('m.person', 's')
            ->addSelect('s')
            ->join('s.user', 'u')
            ->addSelect('u')
            ->join('m.structure', 't')
            ->addSelect('t')
            ->where('m.expiredOn > :now')
            ->setParameter('now', new \DateTime('now'))
            ->andWhere('t.slug = :slug')
            ->setParameter('slug', $slug)
            ->orderBy('s.name', 'asc');

        if (isset($filter['valid'])) {
            if ($filter['valid'])
                $query->andWhere('m.payedOn is not NULL');
            elseif ($filter['valid'] != null)
                $query->andWhere('m.payedOn is NULL');
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

        return $query->getQuery()->getResult();
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
            ->join('m.infos', 'i')
            ->addSelect('i')
            ->join('i.question', 'q')
            ->addSelect('q')
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
}
