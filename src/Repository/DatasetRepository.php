<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Dataset;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

class DatasetRepository extends ServiceEntityRepository
{
    private const array SORTABLE = ['name', 'submittedAt', 'mkt'];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dataset::class);
    }

    /**
     * @return Paginator<Dataset>
     */
    public function paginate(?string $name, string $sort, string $direction, int $page, int $perPage): Paginator
    {
        $sort = \in_array($sort, self::SORTABLE, true) ? $sort : 'submittedAt';
        $direction = 'asc' === strtolower($direction) ? 'ASC' : 'DESC';
        $page = max(1, $page);

        $qb = $this->createQueryBuilder('d');

        if (null !== $name && '' !== trim($name)) {
            $qb->andWhere('d.name LIKE :name')
                ->setParameter('name', '%'.trim($name).'%');
        }

        $qb->orderBy('d.'.$sort, $direction)
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        return new Paginator($qb->getQuery(), false);
    }
}