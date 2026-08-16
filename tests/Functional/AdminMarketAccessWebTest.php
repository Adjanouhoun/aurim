<?php

namespace App\Tests\Functional;

final class AdminMarketAccessWebTest extends AurimWebTestCase
{
    public function testLocalAdministratorOnlySeesAssignedMarket(): void
    {
        $this->client->loginUser($this->localAdmin);
        $this->client->request('GET', '/admin/stocks-par-pays?pays=MR');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.stock-view-tab', 'Sénégal');
        self::assertSelectorCount(1, '.stock-view-tab');
        self::assertSelectorTextNotContains('body', 'Mauritanie');

        $this->client->request('GET', '/admin/utilisateurs');
        self::assertResponseStatusCodeSame(403);
    }

    public function testSuperAdministratorCanOpenUserManagement(): void
    {
        $this->client->loginUser($this->superAdmin);
        $this->client->request('GET', '/admin/utilisateurs');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Utilisateurs de l’administration');
        self::assertSelectorTextContains('.user-admin-table', 'responsable.sn@example.test');
        self::assertSelectorTextContains('.user-admin-table', 'Sénégal');
    }
}
