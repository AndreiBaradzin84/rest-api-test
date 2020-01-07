<?php

namespace App\Controller;

use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use App\Entity\Product;
use Swagger\Annotations as SWG;


/**
 * Product controller.
 * @Route("/api/product", name="api_product")
 */
class ProductsApiController extends FOSRestController {

    const EMPTY_PRODUCT_TABLE_WARNING = 'PRODUCT TABLE EMPTY';
    const EMPTY_VALUE_WARNING = 'NULL VALUES ARE NOT ALLOWED';
    const PRODUCT_EXIST_WARNING = 'PRODUCT ALREADY EXISTS';
    const INCORRECT_PRICE_WARNING = 'PRICE SHOULD BE TYPE OF INTEGER GREATER THAN 0';

    /**
     * Returns all existing Products.
     * @Rest\Get("/all")
     *
     * @SWG\Response(
     *     response=200,
     *     description="List of all existing Products"
     *     )
     * @SWG\Response(
     *     response=404,
     *     description="Product table empty"
     *     )
     *
     * )
     * @return Response
     */
    public function getProductsAction() {
        $repository = $this->getDoctrine()->getRepository( Product::class );
        $products = $repository->findall();

        if (!$products) {
            return $this->handleView( $this->view(self::EMPTY_PRODUCT_TABLE_WARNING, Response::HTTP_NOT_FOUND ) );
        }

        return $this->handleView( $this->view( $products, Response::HTTP_OK ) );
    }

    /**
     * Adds new Product.
     * @Rest\Post("/add")
     *
     * @SWG\Post(
     *     consumes={"application/x-www-form-urlencoded"},
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Product added succesfilly"
     *     )
     * @SWG\Response(
     *     response=409,
     *     description="Product already exists"
     *     )
     * @SWG\Response(
     *     response=400,
     *     description="Invalid parameters supplied"
     *     )
     *
     * @SWG\Parameter(
     *         name="type",
     *         in="formData",
     *         required=true,
     *         type="string",
     *         description="Product type"
     *         )
     * @SWG\Parameter(
     *         name="color",
     *         in="formData",
     *         required=true,
     *         type="string",
     *         description="Product color"
     *          )
     * @SWG\Parameter(
     *         name="size",
     *         in="formData",
     *         required=true,
     *         type="string",
     *         description="Product size"
     * )
     * @SWG\Parameter(
     *         name="price",
     *         in="formData",
     *         required=true,
     *         type="integer",
     *         description="Product price"
     *
     *
     * )
     * @return Response
     */
    public function postProductAction( Request $request ) {

        $product = new Product();

        $type = $request->request->get('type');
        $color = $request->request->get('color');
        $size = $request->request->get('size');
        $price = $request->request->get('price');

        if(empty($type) || empty($color) || empty($size) || empty($price)) {

            return $this->handleView( $this->view(self::EMPTY_VALUE_WARNING, Response::HTTP_BAD_REQUEST) );
        }

        if($price <= 0) {

            return $this->handleView( $this->view(self::INCORRECT_PRICE_WARNING, Response::HTTP_BAD_REQUEST) );
        }

        if($duplicate = $this->isDuplicate($type, $color, $size)) {

            return $this->handleView( $this->view(self::PRODUCT_EXIST_WARNING, Response::HTTP_CONFLICT) );
        }

        $product->setType($type);
        $product->setColor($color);
        $product->setSize($size);
        $product->setPrice($price);

        $em = $this->getDoctrine()->getManager();
        $em->persist($product);
        $em->flush();

        return $this->handleView( $this->view( $product, Response::HTTP_OK) );
    }

    /**
     * Duplicate product validation.
     * @param string $type Product type.
     * @param string $color Product color.
     * @param string $size Product sile.
     *
     * @return Bool
     */
    private function isDuplicate ($type, $color, $size) : bool {

        $repository = $this->getDoctrine()->getRepository(Product::class);

        $duplicate = $repository->findOneBy([
            'type' => $type,
            'color' => $color,
            'size' => $size,
        ]);

        return (bool) $duplicate;
    }
}
