<?php

namespace App\Repository;

use App\Entity\Album;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Album>
 */
class AlbumRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Album::class);
    }

    /**
     * Find public albums for homepage
     * @return Album[]
     */
    public function findPublicAlbums(int $limit = 6): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.photographer', 'u')
            ->andWhere('a.isPublic = :isPublic')
            ->andWhere('a.status = :status')
            ->andWhere('u.status IN (:allowedStatuses)')
            ->setParameter('isPublic', true)
            ->setParameter('status', 'approved')
            ->setParameter('allowedStatuses', ['active', 'suspended'])
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
