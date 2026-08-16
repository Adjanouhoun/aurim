<?php

namespace App\Tests\Functional;

final class LocalizationAndLegalTest extends AurimWebTestCase
{
    public function testHeaderProvidesAccessibleMobileNavigation(): void
    {
        $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('header.site-header');
        self::assertSelectorExists('button[data-menu-toggle][aria-expanded="false"][aria-controls="main-navigation"]');
        self::assertSelectorExists('#main-navigation .mobile-market-selector');
        self::assertSelectorCount(3, '#main-navigation .mobile-locale-selector a');
    }

    public function testCustomerCanSwitchToEnglishAndArabic(): void
    {
        $this->client->request('GET', '/langue/en?returnTo=/boutique');
        self::assertResponseRedirects('/boutique');
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('html[lang="en"][dir="ltr"]');
        self::assertSelectorTextContains('.catalog-hero h1', 'Skincare that reveals your light');
        self::assertSelectorTextContains('.catalog-card h2', 'AURIM Glow Care');

        $this->client->request('GET', '/langue/ar?returnTo=/boutique');
        self::assertResponseRedirects('/boutique');
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('html[lang="ar"][dir="rtl"]');
        self::assertSelectorTextContains('.catalog-hero h1', 'عناية تكشف نورك');
        self::assertSelectorTextContains('.catalog-card h2', 'عناية أوريم للإشراقة');
    }

    public function testLanguageRedirectCannotLeaveTheWebsite(): void
    {
        $this->client->request('GET', '/langue/en?returnTo=https://example.com');

        self::assertResponseRedirects('/');
    }

    public function testLegalPagesAreAvailableFromTheFooter(): void
    {
        foreach ([
            '/mentions-legales',
            '/conditions-generales-de-vente',
            '/politique-de-confidentialite',
            '/politique-des-cookies',
        ] as $path) {
            $this->client->request('GET', $path);
            self::assertResponseIsSuccessful();
            self::assertSelectorExists('.legal-content');
            self::assertSelectorCount(4, '.footer-links a');
        }
    }
}
