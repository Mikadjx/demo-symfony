<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
    }

    // ── isAdmin ───────────────────────────────────────────────────────────────
    // Détermine l'accès à EasyAdmin

    public function testUserIsAdminWhenRoleAdminIsSet(): void
    {
        $this->user->setRoles(['ROLE_ADMIN']);
        $this->assertTrue($this->user->isAdmin());
    }

    public function testUserIsNotAdminByDefault(): void
    {
        $this->assertFalse($this->user->isAdmin());
    }

    public function testUserIsNotAdminWithOnlyRoleUser(): void
    {
        $this->user->setRoles(['ROLE_USER']);
        $this->assertFalse($this->user->isAdmin());
    }

    public function testAdminAlsoHasRoleUser(): void
    {
        // Symfony garantit toujours ROLE_USER même pour un admin
        $this->user->setRoles(['ROLE_ADMIN']);
        $this->assertContains('ROLE_USER', $this->user->getRoles());
    }

    // ── getUserIdentifier ─────────────────────────────────────────────────────
    // L'email est l'identifiant utilisé pour la connexion

    public function testGetUserIdentifierReturnsEmail(): void
    {
        $this->user->setEmail('admin@maison-epouvante.com');
        $this->assertSame('admin@maison-epouvante.com', $this->user->getUserIdentifier());
    }

    public function testGetUserIdentifierReturnsUpdatedEmail(): void
    {
        $this->user->setEmail('old@maison-epouvante.com');
        $this->user->setEmail('new@maison-epouvante.com');
        $this->assertSame('new@maison-epouvante.com', $this->user->getUserIdentifier());
    }

    // ── getRoles ──────────────────────────────────────────────────────────────

    public function testRolesAlwaysContainRoleUser(): void
    {
        $this->assertContains('ROLE_USER', $this->user->getRoles());
    }

    public function testRolesAreUnique(): void
    {
        $this->user->setRoles(['ROLE_USER']);
        $roles = $this->user->getRoles();
        $this->assertSame(array_unique($roles), $roles);
    }
}