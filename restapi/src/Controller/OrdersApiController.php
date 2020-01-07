<?php

namespace App\Controller;

use App\Entity\CountryLimit;
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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Swagger\Annotations as SWG;


/**
 * Order controller.
 * @Route("/api/order", name="api_order")
 */
class OrdersApiController extends FOSRestController {


    const DEFAULT_COUNTRY_CODE = 'US';

    const EMPTY_NO_ORDERS_WARNING = 'ORDER TABLE EMPTY';
    const NO_TYPE_ORDERS_WARNING = 'NO ORDERS FOUND FOR PROVIDED PRODUCT TYPE - ';
    const DRAFT_ORDER_WARNING = 'ORDER SAVED AS A DRAFT BECAUSE OF TOTAL LESS THAN 10. ADD PRODUCTS VIA /api/order/add TO COMPLETE ORDER';
    const EMPTY_ORDER_WARNING = 'EMPTY ORDER';
    const COMPLETED_ORDER_WARNING = 'COMPLETED ORDER CAN`T BE MODIFIED';
    const NOTFOUND_ORDER_WARNING = 'ORDER NOT FOUND. YOU SHOULD CREATE NEW VIA /api/order/new';
    const COUNTRY_ORDER_LIMIT_WARNING = 'REQUEST CAN`T BE COMPLETED BECAUSE OF TIME/COUNTRY LIMITATION. TRY AGAIN IN ';
    const NO_VALID_PRODUCTS_WARNING = 'NO VALID PRODUCTS POSTED';


    private $warnings = [];

    /**
     * Returns all existing Orders.
     * @Rest\Get("/all")
     *
     * @SWG\Response(
     *     response=200,
     *     description="List of all existing Orders"
     *     )
     * @SWG\Response(
     *     response=404,
     *     description="Order table empty"
     *     )
     *
     * @return Response
     */
    public function getOrdersAction() {

        $repository = $this->getDoctrine()->getRepository( ApiOrder::class );
        $orders = $repository->findall();

        if (!$orders) {
            return $this->handleView( $this->view(self::EMPTY_NO_ORDERS_WARNING, Response::HTTP_NOT_FOUND ) );
        }

        return $this->handleView( $this->view( $orders, Response::HTTP_OK ) );
    }

    /**
     * Creates new Order from provided products [id, qty].
     * @Rest\Post("/new")
     *
     * @SWG\Post(
     *     consumes={"application/x-www-form-urlencoded"},
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Order created succesfilly"
     *     )
     * @SWG\Response(
     *     response=202,
     *     description="Draft Order created succesfilly"
     *     )
     * @SWG\Response(
     *     response=400,
     *     description="No valid products provided"
     *     )
     * @SWG\Response(
     *     response=403,
     *     description="Forbidden because of time/country limitation"
     *     )
     *
     *   @SWG\Parameter(
     *       name="products[]",
     *       in="formData",
     *       description="Products array [id, qty]",
     *       required=true,
     *       type="array",
     *       collectionFormat="multi",
     *       @SWG\Items(type="string")
     *   )
     * @return Response
     */
    public function newOrderAction( Request $request ) {

        $order = new ApiOrder();
        $postedProducts = $request->request->all();


        // Workaround for Swagger
        if (array_key_exists('products', $postedProducts) && is_array($postedProducts['products'])) {

            $postedProducts = $this->swaggerWorkaround($postedProducts['products']);

        }


        $orderData['products'] = $this->getValidProducts($postedProducts);
        $orderData['total'] = $this->getOrderTotal($orderData['products']);

        if ($orderData['total'] == 0) {
            $this->warnings['orderWarning'] = self::EMPTY_ORDER_WARNING;
            return $this->handleView( $this->view($this->warnings, Response::HTTP_BAD_REQUEST) );
        }

        $order->setTotal($orderData['total']);
        $order->defineStatus();

        $orderCountryCode = $this->checkOrderOrigin($request->getClientIp());

        $countryLimit = $this->checkOrderCountryLimit( $orderCountryCode );
        if(!is_null($countryLimit)) {
            $this->warnings['orderWarning'] = $countryLimit;
            return $this->handleView( $this->view($this->warnings, Response::HTTP_FORBIDDEN) );
        }

        $statusCode = Response::HTTP_OK;

        if ($order->getStatus() === ApiOrder::STATUS_DRAFT) {
            $this->warnings['orderWarning'] = self::DRAFT_ORDER_WARNING;
            $statusCode = Response::HTTP_ACCEPTED;
        }

        $order->setCountry($orderCountryCode);
        $order->setTimestamp(time ());

        $em = $this->getDoctrine()->getManager();
        $em->persist($order);
        $em->flush();

        $result['orderSummary'] = $order;

        foreach ($orderData['products'] as $productId => $arr) {

            $product = $arr['product'];
            $this->createOrderPack($order, $product, $arr['qty']);
        }

        $result['productList'] = $orderData['products'];

        if(!empty( $this->warnings)) {
            $result['warnings'] = $this->warnings;
        }

        return $this->handleView( $this->view( $result, $statusCode) );
    }


    /**
     * Adds provided products [id, qty] to draft Order.
     * @Rest\Put("/add/{orderId}")
     *
     * @SWG\Put(
     *     consumes={"application/x-www-form-urlencoded"},
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Order updated succesfilly"
     *     )
     * @SWG\Response(
     *     response=202,
     *     description="Draft Order updated succesfilly"
     *     )
     * @SWG\Response(
     *     response=404,
     *     description="Order not found"
     *     )
     * @SWG\Response(
     *     response=400,
     *     description="No valid products provided or completed order id provided"
     *     )
     * @SWG\Response(
     *     response=403,
     *     description="Forbidden because of time/country limitation"
     *     )
     *
     *   @SWG\Parameter(
     *       name="products[]",
     *       in="formData",
     *       description="Products array [id, qty]",
     *       required=true,
     *       type="array",
     *       collectionFormat="multi",
     *       @SWG\Items(type="string")
     *   )
     * @return Response
     */
    public function addProductsToOrderAction( Request $request ) {

        $orderId = $request->get('orderId');
        $repository = $this->getDoctrine()->getRepository( ApiOrder::class );
        $order = $repository->find($orderId);

        if (!$order) {
            $result['orderWarning'] = self::NOTFOUND_ORDER_WARNING;
            return $this->handleView( $this->view($result, Response::HTTP_NOT_FOUND) );
        }

        if ($order->getStatus() != ApiOrder::STATUS_DRAFT) {
            $result['orderWarning'] = self::COMPLETED_ORDER_WARNING;
            return $this->handleView( $this->view($result, Response::HTTP_BAD_REQUEST) );
        }

        $postedProducts = $request->request->all();

        // Workaround for Swagger
        if (array_key_exists('products', $postedProducts) && is_array($postedProducts['products'])) {

            $postedProducts = $this->swaggerWorkaround($postedProducts['products']);

        }

        $orderData['products'] = $this->getValidProducts($postedProducts);

        if(empty($orderData['products'])) {
            $result['orderWarning'] = self::NO_VALID_PRODUCTS_WARNING;
            return $this->handleView( $this->view($result, Response::HTTP_BAD_REQUEST) );
        }

        $orderCountryCode = $this->checkOrderOrigin($request->getClientIp());

        $countryLimit = $this->checkOrderCountryLimit( $orderCountryCode );
        if(!is_null($countryLimit)) {
            $this->warnings['orderWarning'] = $countryLimit;
            return $this->handleView( $this->view($this->warnings, Response::HTTP_FORBIDDEN) );
        }

        $orderData['total'] = $order->getTotal() + $this->getOrderTotal($orderData['products']);
        $order->setTotal($orderData['total']);
        $order->defineStatus();

        $existingOrderPacks = $this->getDoctrine()->getRepository(OrderPack::class)->getOrderPacks($order);

        $result['addedProductList'] = $orderData['products'];

        foreach ($existingOrderPacks as $k => $orderPack) {
            $orderPackProductId = $orderPack->getProduct()->getId();
            if (array_key_exists($orderPackProductId, $orderData['products'])) {
                $orderPack->setQuantity($orderPack->getQuantity() + $orderData['products'][$orderPackProductId]['qty']);
                unset($orderData['products'][$orderPackProductId]);
            }
        }

        foreach ($orderData['products'] as $id => $prod) {
            $this->createOrderPack($order, $prod['product'], $prod['qty']);
        }

        $statusCode = Response::HTTP_OK;

        if ($order->getStatus() === ApiOrder::STATUS_DRAFT) {
            $this->warnings['orderWarning'] = self::DRAFT_ORDER_WARNING;
            $statusCode = Response::HTTP_ACCEPTED;
        }

        $order->setTimestamp(time ());
        $em = $this->getDoctrine()->getManager();
        $em->persist($order);
        $em->flush();

        $result['orderSummary'] = $order;
        if(!empty( $this->warnings)) {
            $result['warnings'] = $this->warnings;
        }

        return $this->handleView( $this->view( $result, $statusCode) );
    }


    /**
     * Returns all existing Orders by product type.
     * @Rest\Get("/product/{type}")
     *
     * @SWG\Response(
     *     response=200,
     *     description="List of existing Orders Orders for provided product type"
     *     )
     * @SWG\Response(
     *     response=404,
     *     description="Orders not found for provided product type"
     *     )
     *
     * @return Response
     */
    public function getOrderByProductType( Request $request ) {

        $type = $request->get('type');
        $order = $this->getDoctrine()->getRepository(ApiOrder::class)->findOrdersByProductType($type);

        if(!$order){
            return $this->handleView( $this->view( self::NO_TYPE_ORDERS_WARNING . $type, Response::HTTP_NOT_FOUND) );
        }

        return $this->handleView( $this->view( $order, Response::HTTP_OK) );
    }

    /**
     * Get valid product from POST user data.
     *
     * @param array $postedProducts Set of product ids and quantities provided by user.
     *
     * @return array Returns a set of valid product ids and quantities.
     */
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

    /**
     * Get order total price.
     *
     * @param array $products Product set to count prices.
     * @param int $total Order total (if add action performed).
     *
     *
     * @return int $total order total price.
     */
    private function getOrderTotal( array $products, int $total = 0 ) {

        foreach ($products as $id => $arr) {

            $total += $arr['product']->getPrice()*$arr['qty'];

        }

        return $total;
    }

    /**
     * Creates OrderPack (Order-Product-Quantity set) for given params.
     *
     * @param ApiOrder $order Order entity.
     * @param Product $product Product entity.
     * @param int $quantity Product quantity.
     *
     * @return null
     */
    private function createOrderPack( ApiOrder $order, Product $product, int $quantity ) {

        $orderPack = new OrderPack();

        $orderPack->setOrder($order);
        $orderPack->setProduct($product);
        $orderPack->setQuantity($quantity);

        $em = $this->getDoctrine()->getManager();
        $em->persist($orderPack);
        $em->flush();

        return;
    }

    /**
     * Checks Order Origin by IP using web-service.
     *
     * @param string $ip API user API.
     *
     * @return string $countryCode User country code.
     */
    private function checkOrderOrigin ($ip) {

        $json = file_get_contents( 'https://www.iplocate.io/api/lookup/' . $ip );
        if ($json) {
            $result = json_decode( $json, true);
            $countryCode = $result ['country_code'];
        }

        return !empty($countryCode) ? $countryCode : self::DEFAULT_COUNTRY_CODE;
    }

    /**
     * Checks Order/Country time limit.
     *
     * @param $countryCode $ip API user API.
     *
     * @return string|null $result Result mesaage.
     */
    private function checkOrderCountryLimit ($countryCode) {

        $previousOrder = $this->getDoctrine()->getRepository( ApiOrder::class )->findLatestCountryOrder($countryCode);
        $result = null;

        if (is_null($previousOrder)) {
            return $result;
        }

        $countryLimit = $this->getDoctrine()->getRepository( CountryLimit::class )->findOneBy(['countryCode' => $countryCode]);
        $countryLimitTime = $countryLimit->getTimeLimit();
        $previousOrderTimestamp = $previousOrder->getTimestamp();
        $timestamp = time ();
        $timeGap = $countryLimitTime - ($timestamp - $previousOrderTimestamp);

        if($timeGap > 0) {
            $result = self::COUNTRY_ORDER_LIMIT_WARNING . $timeGap . ' SECONDS';
        }

        return $result;
    }


    /**
     * Swgger Workaround.
     *
     * @param array $postedProducts.
     *
     * @return array $swgPostedProducts
     */
    private function swaggerWorkaround (array $postedProducts) {

        $swgPostedProducts = [];

        foreach ($postedProducts as $k => $v) {
            $temp = explode(",", $v);
            if(count($temp) == 2) {
                $swgPostedProducts[$temp[0]] = $temp[1];
            }
        }

        return $swgPostedProducts;
    }
}
