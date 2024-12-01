<?php

namespace App\Tests\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProductControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/product/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('table'); // Assumes there's a table displaying products
    }

    public function testNewProduct()
    {
        $client = static::createClient();

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Product::class));
        $entityManager->expects($this->once())
            ->method('flush');

        $imagePath = __DIR__ . '/test-image.jpg';
        file_put_contents($imagePath, 'dummy content');
        $uploadedFile = new UploadedFile($imagePath, 'test-image.jpg', 'image/jpeg', null, true);

        $crawler = $client->request('GET', '/admin/product/new');

        $form = $crawler->selectButton('Save')->form([
            'product[name]' => 'Test Product',
            'product[description]' => 'Test Description',
            'product[price]' => 100,
            'product[imageFile]' => $uploadedFile,
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/admin/product/');
        unlink($imagePath);
    }

    public function testShowProduct()
    {
        $client = static::createClient();
        $product = new Product();
        $product->setName('Test Product');

        $productRepository = $this->createMock(ProductRepository::class);
        $productRepository->method('find')
            ->willReturn($product);

        $client->request('GET', '/admin/product/1');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Test Product');
    }

    public function testEditProduct()
    {
        $client = static::createClient();

        $product = new Product();
        $product->setName('Test Product');

        $crawler = $client->request('GET', '/admin/product/1/edit');

        $form = $crawler->selectButton('Save')->form([
            'product[name]' => 'Updated Product',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/admin/product/');
    }

    public function testDeleteProduct()
    {
        $client = static::createClient();

        $product = new Product();
        $product->setName('Test Product');

        $client->request('POST', '/admin/product/1', [
            '_token' => 'valid_token', // Ensure CSRF token is valid
        ]);

        $this->assertResponseRedirects('/admin/product/');
    }
}
