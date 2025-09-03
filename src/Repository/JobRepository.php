<?php

namespace App\Repository;

use App\Entity\Job;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Job>
 */
class JobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Job::class);
    }

    /**
     * Update an existing job or create a new one if it doesn't exist
     */
    public function updateOrCreateJob(
        string $original_message,
        ?string $location = null,
        ?array $result = null,
        ?string $query = null,
        ?string $messageId = null,
        ?\DateTimeImmutable $sentAtDatetime = null
    ): Job {
        // Try to find an existing job by original_query and creation date within 2 minutes of sentAtDatetime
        $queryBuilder = $this->createQueryBuilder('j')
            ->where('j.original_query = :originalMessage')
            ->setParameter('originalMessage', $original_message);
        
        // Only add date comparison if sentAtDatetime is provided
        if ($sentAtDatetime !== null) {
            // Create date range: sentAtDatetime ± 2 minutes
            $startDate = (clone $sentAtDatetime)->modify('-2 minutes');
            $endDate = (clone $sentAtDatetime)->modify('+2 minutes');
            
            $queryBuilder
                ->andWhere('j.createdAt >= :startDate')
                ->andWhere('j.createdAt <= :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate);
        }
        
        $existingJob = $queryBuilder
            ->getQuery()
            ->getOneOrNullResult();
        
        if ($existingJob) {
            // Update existing job
            if ($location !== null) {
                $existingJob->setLocation($location);
            }
            if ($result !== null) {
                $existingJob->setResult($result);
            }
            if ($query !== null) {
                $existingJob->setQuery($query);
            }
            if ($messageId !== null) {
                $existingJob->setMessageId($messageId);
            }
            if ($sentAtDatetime !== null) {
                $existingJob->setSentAt($sentAtDatetime);
            }
            
            $existingJob->setResolvedAt(new \DateTimeImmutable());
            
            $this->_em->flush();
            return $existingJob;
        } else {
            // Create a new job
            $job = new Job();
            $job->setOriginalQuery($original_message);
            $job->setLocation($location);
            $job->setResult($result ?? []);
            $job->setQuery($query);
            $job->setMessageId($messageId);
            $job->setSentAt($sentAtDatetime);
            $job->setCreatedAt(new \DateTimeImmutable());
            
            $this->_em->persist($job);
            $this->_em->flush();
            return $job;
        }
    }

//    /**
//     * @return Job[] Returns an array of Job objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('j')
//            ->andWhere('j.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('j.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Job
//    {
//        return $this->createQueryBuilder('j')
//            ->andWhere('j.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
