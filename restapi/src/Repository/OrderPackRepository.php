<?php


namespace App\Repository;

use App\Entity\ApiOrder;
use App\Entity\OrderPack;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class OrderPackRepository extends ServiceEntityRepository {

    public function __construct( ManagerRegistry $registry ) {
        parent::__construct( $registry, OrderPack::class );
    }

    public function getOrderPacks($order) {

        $orderPacks = $this->getEntityManager()
                            ->createQueryBuilder()
                            ->select('op')
                            ->from(OrderPack::class, 'op')
                            ->innerJoin(ApiOrder::class, 'ao', 'with', 'ao.id = op.order')
                            ->innerJoin(Product::class, 'p', 'with', 'p.id = op.product')
                            ->where('op.order = :order')
                            ->setParameter('order', $order)
                            ->getQuery()
                            ->getResult();

        return $orderPacks;

    }

}