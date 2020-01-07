<?php


namespace App\Tests;

use App\Entity\ApiOrder;
use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;

class OrdersApiControllerTest extends WebTestCase
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

    public function testListAllOrdersEmpty()
    {
        $orders = $this->entityManager
            ->getRepository(APIOrder::class)
            ->findAll();

        foreach ($orders as $order) {
            $this->entityManager->remove($order);
        }

        $this->entityManager->flush();

        $client = static::createClient();

        $client->request('GET', 'api/order/all');

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testOrderAddBadProducts()
    {

        $client = static::createClient();

        $content = [
            'bad' => 3,
        ];

        $client->request('POST', 'api/order/new', $content, [], []);

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function testOrderAdd()
    {

        $this->entityManager->getRepository(Product::class);

        $product = new Product();
        $product->setType('Pen');
        $product->setColor('Red');
        $product->setSize('Reg');
        $product->setPrice(5);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $client = static::createClient();

        $content = [
            $product->getId() => 3,
        ];

        $client->request('POST', 'api/order/new', $content, [], []);

        $product = $this->entityManager
            ->getRepository(Product::class)
            ->findOneBy([
                'type' => 'Pen',
                'color' => 'Red',
                'size' => 'Reg',
            ]);

        if($product) {
            $this->entityManager->remove($product);
            $this->entityManager->flush();
        }

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testOrderAdd403()
    {

        $this->entityManager->getRepository(Product::class);

        $product = new Product();
        $product->setType('Pen');
        $product->setColor('Blue');
        $product->setSize('Reg');
        $product->setPrice(5);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $client = static::createClient();

        $content = [
            $product->getId() => 1,
        ];

        $client->request('POST', 'api/order/new', $content, [], []);

        $product = $this->entityManager
            ->getRepository(Product::class)
            ->findOneBy([
                'type' => 'Pen',
                'color' => 'Blue',
                'size' => 'Reg',
            ]);

        if($product) {
            $this->entityManager->remove($product);
            $this->entityManager->flush();
        }

        $this->assertEquals(403, $client->getResponse()->getStatusCode());

        echo "\n Sleeping for 30 seconds ... \n";
    }

    public function testOrderDraftAdd()
    {
        sleep(30);

        $this->entityManager->getRepository(Product::class);

        $product = new Product();
        $product->setType('Pen');
        $product->setColor('Green');
        $product->setSize('Reg');
        $product->setPrice(5);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $client = static::createClient();

        $content = [
            $product->getId() => 1,
        ];

        $client->request('POST', 'api/order/new', $content, [], []);

        $product = $this->entityManager
            ->getRepository(Product::class)
            ->findOneBy([
                'type' => 'Pen',
                'color' => 'Green',
                'size' => 'Reg',
            ]);

        if($product) {
            $this->entityManager->remove($product);
            $this->entityManager->flush();
        }

        $this->assertEquals(202, $client->getResponse()->getStatusCode());
    }

    public function testListAllOrders()
    {


        $client = static::createClient();

        $client->request('GET', 'api/order/all');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }



}