<?php

namespace App\Repository;

use App\Entity\Photo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Photo>
 */
class PhotoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Photo::class);
    }

    /**
     * Find public photos for the public feed
     * @return Photo[]
     */
    public function findPublicPhotos(int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.photographer', 'u')
            ->andWhere('p.isPublic = :isPublic')
            ->andWhere('p.status = :status')
            ->andWhere('u.status IN (:allowedStatuses)')
            ->setParameter('isPublic', true)
            ->setParameter('status', 'approved')
            ->setParameter('allowedStatuses', ['active', 'suspended'])
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find photos by photographer
     * @return Photo[]
     */
    public function findByPhotographer($photographer, bool $publicOnly = false): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.photographer = :photographer')
            ->setParameter('photographer', $photographer)
            ->orderBy('p.createdAt', 'DESC');

        if ($publicOnly) {
            $qb->andWhere('p.isPublic = :isPublic')
               ->andWhere('p.status = :status')
               ->setParameter('isPublic', true)
               ->setParameter('status', 'approved');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find photos by album with optional approval filtering
     * @return Photo[]
     */
    public function findPhotosInAlbum($album, bool $approvedOnly = false): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.album = :album')
            ->setParameter('album', $album)
            ->orderBy('p.createdAt', 'DESC');

        if ($approvedOnly) {
            $qb->andWhere('p.status = :status')
               ->setParameter('status', 'approved');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find standalone photos (not in albums)
     * @return Photo[]
     */
    public function findStandalonePhotos(int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.photographer', 'u')
            ->andWhere('p.album IS NULL')
            ->andWhere('p.isPublic = :isPublic')
            ->andWhere('p.status = :status')
            ->andWhere('u.status IN (:allowedStatuses)')
            ->setParameter('isPublic', true)
            ->setParameter('status', 'approved')
            ->setParameter('allowedStatuses', ['active', 'suspended'])
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Search photos by title or description
     * @return Photo[]
     */
    public function searchPhotos(string $query, bool $publicOnly = true): array
    {
        $qb = $this->createQueryBuilder('p')
            ->join('p.photographer', 'u')
            ->andWhere('p.title LIKE :query OR p.description LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('p.createdAt', 'DESC');

        if ($publicOnly) {
            $qb->andWhere('p.isPublic = :isPublic')
               ->andWhere('p.status = :status')
               ->andWhere('u.status IN (:allowedStatuses)')
               ->setParameter('isPublic', true)
               ->setParameter('status', 'approved')
               ->setParameter('allowedStatuses', ['active', 'suspended']);
        }

        return $qb->getQuery()->getResult();
    }
}
