<?php


namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PostControllerTest extends WebTestCase
{
    public function testShowPost()
    {
        $client = static::createClient();

        $client->request('GET', 'api/product/all');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}