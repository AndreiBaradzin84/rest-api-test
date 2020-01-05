<?php


namespace App\Repository;

use App\Entity\ApiOrder;
use App\Entity\OrderPack;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class ApiOrderRepository extends ServiceEntityRepository {

    public function __construct( ManagerRegistry $registry ) {
        parent::__construct( $registry, ApiOrder::class );
    }

    public function findOrdersByProductType($type = 'hoodie'): array {

        $orders = $this->getEntityManager()
                       ->createQueryBuilder()
                       ->select('ao')
                       ->from(ApiOrder::class, 'ao')
                       ->innerJoin(OrderPack::class, 'op', 'with', 'ao.id = op.order')
                       ->innerJoin(Product::class, 'p', 'with', 'p.id = op.product')
                       ->where('p.type = :type')
                       ->setParameter('type', $type)
                       ->getQuery()
                       ->getResult();

        return $orders;

    }

}