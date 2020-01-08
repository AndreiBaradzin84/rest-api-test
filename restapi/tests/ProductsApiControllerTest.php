<?php


namespace App\Tests;

use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;

class ProductsApiControllerTest extends WebTestCase
{

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    protected function setUp() : void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
                                      ->get('doctrine')
                                      ->getManager();
    }

    public function testProductAdd()
    {
        $product = $this->entityManager
            ->getRepository(Product::class)
            ->findOneBy([
                'type' => 'Pen',
                'color' => 'Silver',
                'size' => 'Reg',
            ]);

        if($product) {
            $this->entityManager->remove($product);
            $this->entityManager->flush();
        }

        $client = static::createClient();

        $content = [
            'type' => 'Pen',
            'color' => 'Silver',
            'size' => 'Reg',
            'price' => '5',
        ];


        $client->request('POST', 'api/product/add', $content, [], []);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testProductDuplicate()
    {
        $client = static::createClient();

        $content = [
            'type' => 'Pen',
            'color' => 'Silver',
            'size' => 'Reg',
            'price' => '5',
        ];

        $client->request('POST', 'api/product/add', $content, [], []);

        $this->assertEquals(409, $client->getResponse()->getStatusCode());
    }

    public function testProductEmptyValue()
    {
        $client = static::createClient();

        $content = [
            'type' => 'Type',
            'color' => '',
            'size' => 'SZ',
            'price' => '',
        ];

        $client->request('POST', 'api/product/add', $content, [], []);

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

}