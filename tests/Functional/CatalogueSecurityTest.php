<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CatalogueSecurityTest extends WebTestCase
{
    private function createUser(string $email, string $password, array $roles = ['ROLE_USER']): void
    {
        $container = static::getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get('security.user_password_hasher');

        // Nettoyer l'utilisateur s'il existe déjà
        $existing = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing) {
            $em->remove($existing);
            $em->flush();
        }

        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setPassword($hasher->hashPassword($user, $password));
        $em->persist($user);
        $em->flush();
    }

    // ── Accès non authentifié ─────────────────────────────────────────────────

    public function testCatalogueRedirectsToLoginWhenNotAuthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', '/product');

        // Sans connexion → redirect vers /login
        $this->assertResponseRedirects('/login');
    }

    public function testLoginPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
    }

    // ── Connexion échouée ─────────────────────────────────────────────────────

    public function testLoginFailsWithInvalidCredentials(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        $client->submitForm('Se connecter', [
            '_username' => 'inconnu@maison-epouvante.com',
            '_password' => 'mauvais-mot-de-passe',
        ]);

        $this->assertResponseRedirects('/login');
        $client->followRedirect();
        $this->assertSelectorExists('.alert-danger');
    }

    // ── Connexion réussie → accès catalogue ───────────────────────────────────

    public function testAuthenticatedUserCanAccessCatalogue(): void
    {
        $client = static::createClient();
        $this->createUser('user@maison-epouvante.com', 'password123');

        $client->request('GET', '/login');
        $client->submitForm('Se connecter', [
            '_username' => 'user@maison-epouvante.com',
            '_password' => 'password123',
        ]);

        $client->followRedirect();
        $client->request('GET', '/product');
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
    }

    // ── Admin accède aussi au catalogue ───────────────────────────────────────

    public function testAdminCanAlsoAccessCatalogue(): void
    {
        $client = static::createClient();
        $this->createUser('admin@maison-epouvante.com', 'admin123', ['ROLE_ADMIN']);

        $client->request('GET', '/login');
        $client->submitForm('Se connecter', [
            '_username' => 'admin@maison-epouvante.com',
            '_password' => 'admin123',
        ]);

        $client->followRedirect();
        $client->request('GET', '/product');
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
    }
}