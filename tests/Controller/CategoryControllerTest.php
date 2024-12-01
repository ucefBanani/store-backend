<?php

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class CategoryControllerTest extends WebTestCase
{
    public function testIndexPageAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/category/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Category List');
    }

    public function testCreateCategoryUnauthorized(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/category/new');

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testCreateCategoryAuthorized(): void
    {
        $client = static::createClient();

        // Simulate a user with the appropriate role
        $client->loginUser($this->createMockAdminUser());

        $crawler = $client->request('GET', '/admin/category/new');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');

        // Submit form
        $form = $crawler->selectButton('Save')->form([
            'category[name]' => 'Test Category',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects('/admin/category/');
    }

    public function testShowCategoryUnauthorized(): void
    {
        $client = static::createClient();
        $category = $this->createCategory();

        $client->request('GET', '/admin/category/' . $category->getId());
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testEditCategory(): void
    {
        $client = static::createClient();
        $client->loginUser($this->createMockAdminUser());

        $category = $this->createCategory();

        $crawler = $client->request('GET', '/admin/category/' . $category->getId() . '/edit');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Save')->form([
            'category[name]' => 'Updated Category',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects('/admin/category/');
    }

    public function testDeleteCategory(): void
    {
        $client = static::createClient();
        $client->loginUser($this->createMockAdminUser());

        $category = $this->createCategory();

        $client->request('POST', '/admin/category/' . $category->getId(), [
            '_token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('delete' . $category->getId()),
        ]);

        $this->assertResponseRedirects('/admin/category/');
    }

    private function createMockAdminUser()
    {
        // Replace with logic to create a mock user with admin privileges
        return $this->getContainer()->get('doctrine')->getRepository(User::class)->findOneByRole('ROLE_ADMIN');
    }

    private function createCategory(): Category
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $category = new Category();
        $category->setName('Sample Category');

        $entityManager->persist($category);
        $entityManager->flush();

        return $category;
    }
}
