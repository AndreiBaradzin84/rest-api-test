<?php

namespace App\Controller;

use App\Entity\OrderPack;
use FOS\RestBundle\View\View;
use JMS\Serializer\Tests\Fixtures\Order;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use App\Entity\Product;
use App\Entity\ApiOrder;


/**
 * Order controller.
 * @Route("/api/order", name="api_order")
 */
class OrdersApiController extends FOSRestController {

    const DRAFT_ORDER_WARNING = 'ORDER SAVED AS A DRAFT BECAUSE OF TOTAL LESS THAN 10. ADD PRODUCTS VIA /api/order/add TO COMPLETE ORDER';
    const EMPTY_ORDER_WARNING = 'EMPTY ORDER';
    const COMPLETED_ORDER_WARNING = 'COMPLETED ORDER CAN`T BE MODIFIED';
    const NOTFOUND_ORDER_WARNING = 'ORDER NOT FOUND. YOU SHOULD CREATE NEW VIA /api/order/new';

    private $warnings = [];

    /**
     * Lists all Orders.
     * @Rest\Get("/all")
     *
     * @return Response
     */
    public function getOrdersAction() {


        $repository = $this->getDoctrine()->getRepository( ApiOrder::class );
        $order = $repository->find(3);


        $existingOrderPacks = $this->getDoctrine()
                                   ->getRepository(OrderPack::class)
                                   ->getOrderPacks($order);

        var_dump($existingOrderPacks); die;

//        $repository = $this->getDoctrine()->getRepository( ApiOrder::class );
//        $products = $repository->findall();
//
//        if (!$products) {
//            return $this->handleView( $this->view('ORDER TABLE EMPTY', Response::HTTP_CONFLICT ) );
//        }

        return $this->handleView( $this->view( $existingOrderPacks, Response::HTTP_OK ) );
    }

    /**
     * Place new Order.
     * @Rest\Post("/new")
     *
     * @return Response
     */
    public function newOrderAction( Request $request ) {

        $order = new ApiOrder();
        $postedProducts = $request->request->all();

        $orderData['products'] = $this->getValidProducts($postedProducts);

        $orderData['total'] = $this->getOrderTotal($orderData['products']);

        if ($orderData['total'] == 0) {
            $this->warnings['orderWarning'] = self::EMPTY_ORDER_WARNING;
            return $this->handleView( $this->view($this->warnings, Response::HTTP_NOT_ACCEPTABLE) );
        }

        $order->setTotal($orderData['total']);

        $order->defineStatus();
        if ($order->getStatus() === 'draft') {
            $this->warnings['orderWarning'] = self::DRAFT_ORDER_WARNING;
        }

        $order->setTimestamp(time ());

        $em = $this->getDoctrine()->getManager();
        $em->persist($order);
        $em->flush();

        $result['orderSummary'] = $order;

        foreach ($orderData['products'] as $productId => $arr) {

            $orderPack = new OrderPack();
            $product = $arr['product'];
            $orderPack->setOrder($order);
            $orderPack->setProduct($product);
            $orderPack->setQuantity($arr['qty']);

            $em = $this->getDoctrine()->getManager();
            $em->persist($orderPack);
            $em->flush();

        }

        $result['productList'] = $orderData['products'];

        if(!empty( $this->warnings)) {
            $result['warnings'] = $this->warnings;
        }

        return $this->handleView( $this->view( $result, Response::HTTP_OK) );
    }

    /**
     * Add products to complete draft Order.
     * @Rest\Post("/add/{orderId}")
     *
     * @return Response
     */
    public function addProductsToOrderAction( Request $request ) {

        $orderId = $request->get('orderId');

        $repository = $this->getDoctrine()->getRepository( ApiOrder::class );
        $order = $repository->find($orderId);

        if (!$order) {
            $result['orderWarning'] = self::NOTFOUND_ORDER_WARNING;
            return $this->handleView( $this->view($result, Response::HTTP_NOT_ACCEPTABLE) );
        }

        if ($order->getStatus() != 'draft') {
            $result['orderWarning'] = self::COMPLETED_ORDER_WARNING;
            return $this->handleView( $this->view($result, Response::HTTP_NOT_ACCEPTABLE) );
        }

        $postedProducts = $request->request->all();

        $orderData['products'] = $this->getValidProducts($postedProducts);

        if(empty($orderData['products'])) {
            $result['orderWarning'] = 'NO VALID PRODUCTS';
            return $this->handleView( $this->view($result, Response::HTTP_NOT_ACCEPTABLE) );
        }

        $existingOrderPacks = $this->getDoctrine()
                                   ->getRepository(OrderPack::class)
                                   ->getOrderPacks($order);
        var_dump($existingOrderPacks); die;

        $orderData['total'] = $order->getTotal() + $this->getOrderTotal($orderData['products']);

        $order->setTotal($orderData['total']);

        $order->defineStatus();
        if ($order->getStatus() === 'draft') {
            $this->warnings['orderWarning'] = self::DRAFT_ORDER_WARNING;
        }



        foreach ($existingOrderPacks as $orderPack) {
            $id = $orderPack->getId();

        }

        $order->setTimestamp(time ());

        $contents = $orderData['contents'];
        $order->setContent(serialize($contents));
        $order->setTimestamp(time ());


        if ($orderData['total'] < 10) {
            $result['orderWarning'] = self::DRAFT_ORDER_WARNING;
            $order->setStatus('draft');
        } else {
            $order->setStatus('complete');
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($order);
        $em->flush();

        $result['order'] = $order;


        foreach ($contents as $productId => $qty) {

            $orderPack = new OrderPack();
            $orderPack->setOrder($order->getId());
            $orderPack->setProductId($productId);
            $orderPack->setQuantity($qty);
            $em = $this->getDoctrine()->getManager();
            $em->persist($orderPack);
            $em->flush();

        }

        return $this->handleView( $this->view( $result, Response::HTTP_OK) );

    }

    /**
     * Get order by productType.
     * @Rest\Get("/product/{type}")
     *
     * @return Response
     */
    public function getOrderByProductType( Request $request ) {

        $type = $request->get('type');

                $order = $this->getDoctrine()
                        ->getRepository(ApiOrder::class)
                        ->findOrdersByProductType($type);

        return $this->handleView( $this->view( $order, Response::HTTP_OK) );

    }

    /**
     * Fills/adds order contents with given products.
     *
     * @return Array
     */
    private function fillOrderContents( $products, $contents = [], $total = 0 ) {


        $data['contents'] = $contents;
        $data['total'] = $total;
        $data['productWarning'] = [];

        var_dump($data); die;



        foreach ($products as $id => $qty) {
            $product = $repository->find($id);
            if (!$product) {
                $data['productWarning'][] = 'PRODUCT ID ' . $id . ' DOES NOT EXIST';
                continue;
            }

            if ($qty <= 0) {
                $data['productWarning'][] = 'INVALID QUANTITY FOR PRODUCT ID ' . $id;
                continue;
            }

            $productPrice = $product->getPrice();
            $productTotal = $qty * $productPrice;
            $data['total'] = $data['total'] + $productTotal;
            if (array_key_exists($id, $data['contents'])) {
                $data['contents'][$id] = $data['contents'][$id] + $qty;
            }
            else {
                $data['contents'][$id] = $qty;
            }

        }

        return $data;

    }

    private function getValidProducts( array $postedProducts ) {

        $validProducts = [];
        $repository = $this->getDoctrine()->getRepository( Product::class );

        foreach ($postedProducts as $id => $qty) {

            $product = $repository->find($id);

            if (!$product) {
                $this->warnings['productWarning'][] = 'PRODUCT ID ' . $id . ' DOES NOT EXIST';
                continue;
            }

            if ($qty <= 0) {
                $this->warnings['productWarning'][] = 'INVALID QUANTITY FOR PRODUCT ID ' . $id;
                continue;
            }

            $validProducts[$id] = ['product' => $product, 'qty' => $qty];

        }

        return $validProducts;
    }

    private function getOrderTotal( array $products, $total = 0 ) {

        foreach ($products as $id => $arr) {

            $total += $arr['product']->getPrice()*$arr['qty'];

        }

        return $total;
    }

}
