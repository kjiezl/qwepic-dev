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
            ->andWhere('p.isPublic = :isPublic')
            ->setParameter('isPublic', true)
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
               ->setParameter('isPublic', true);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find photos by album
     * @return Photo[]
     */
    public function findByAlbum($album): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.album = :album')
            ->setParameter('album', $album)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find standalone photos (not in albums)
     * @return Photo[]
     */
    public function findStandalonePhotos(int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.album IS NULL')
            ->andWhere('p.isPublic = :isPublic')
            ->setParameter('isPublic', true)
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
            ->andWhere('p.title LIKE :query OR p.description LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('p.createdAt', 'DESC');

        if ($publicOnly) {
            $qb->andWhere('p.isPublic = :isPublic')
               ->setParameter('isPublic', true);
        }

        return $qb->getQuery()->getResult();
    }
}
