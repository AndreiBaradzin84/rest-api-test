<?php

namespace App\Controller;

use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use App\Entity\Product;


/**
 * Product controller.
 * @Route("/api/product", name="api_product")
 */
class ProductsApiController extends FOSRestController {
    /**
     * Lists all Products.
     * @Rest\Get("/all")
     *
     * @return Response
     */
    public function getProductsAction() {
        $repository = $this->getDoctrine()->getRepository( Product::class );
        $products = $repository->findall();

        if (!$products) {
            return $this->handleView( $this->view('PRODUCT TABLE EMPTY', Response::HTTP_CONFLICT ) );
        }

        return $this->handleView( $this->view( $products, Response::HTTP_OK ) );
    }

    /**
     * Add Product.
     * @Rest\Post("/add")
     *
     * @return Response
     */
    public function postProductAction( Request $request ) {

        $product = new Product();

        $type = $request->request->get('type');
        $color = $request->request->get('color');
        $size = $request->request->get('size');
        $price = $request->request->get('price');

        if(empty($type) || empty($color) || empty($size) || empty($price)) {

            return $this->handleView( $this->view('NULL VALUES ARE NOT ALLOWED', Response::HTTP_NOT_ACCEPTABLE) );
        }

        if($duplicate = $this->isDuplicate($type, $color, $size)) {

            return $this->handleView( $this->view('PRODUCT ALREADY EXISTS', Response::HTTP_CONFLICT) );
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

        if ( $duplicate ) {
            return true;
        }

        return false;

    }
}
