<?php

namespace App\Repository;

use App\Entity\Subscriber;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Subscriber> */
class SubscriberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscriber::class);
    }

    public function findOneByEmail(string $email): ?Subscriber
    {
        return $this->findOneBy(['email' => strtolower(trim($email))]);
    }

    public function findOneByToken(string $token): ?Subscriber
    {
        return $this->findOneBy(['confirmationToken' => $token]);
    }

    public function countConfirmed(): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.status = :s')
            ->setParameter('s', Subscriber::STATUS_CONFIRMED)
            ->getQuery()->getSingleScalarResult();
    }
}
