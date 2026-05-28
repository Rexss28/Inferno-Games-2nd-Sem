<?php

namespace App\Repository;

use App\Entity\ActivityLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityLog>
 */
class ActivityLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityLog::class);
    }

    public function findByFilters(array $filters = [], ?int $limit = null)
    {
        $qb = $this->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC');

        if (!empty($filters['user'])) {
            $qb->andWhere('a.username LIKE :user')
                ->setParameter('user', '%' . $filters['user'] . '%');
        }

        if (!empty($filters['action'])) {
            $qb->andWhere('a.action = :action')
                ->setParameter('action', $filters['action']);
        }

        if (!empty($filters['date'])) {
            $date = new \DateTime($filters['date']);
            $startDate = (clone $date)->setTime(0, 0, 0);
            $endDate = (clone $date)->setTime(23, 59, 59);
            
            $qb->andWhere('a.createdAt BETWEEN :startDate AND :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate);
        }

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get logs created after a specific ID (for real-time updates)
     */
    public function findLogsAfterId(int $afterId, array $filters = [], ?int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.id > :afterId')
            ->setParameter('afterId', $afterId)
            ->orderBy('a.id', 'DESC');

        if (!empty($filters['user'])) {
            $qb->andWhere('a.username LIKE :user')
                ->setParameter('user', '%' . $filters['user'] . '%');
        }

        if (!empty($filters['action'])) {
            $qb->andWhere('a.action = :action')
                ->setParameter('action', $filters['action']);
        }

        if (!empty($filters['date'])) {
            $date = new \DateTime($filters['date']);
            $startDate = (clone $date)->setTime(0, 0, 0);
            $endDate = (clone $date)->setTime(23, 59, 59);
            
            $qb->andWhere('a.createdAt BETWEEN :startDate AND :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate);
        }

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    //    /**
    //     * @return ActivityLog[] Returns an array of ActivityLog objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ActivityLog
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}