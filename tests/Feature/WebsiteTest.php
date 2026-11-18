<?php

namespace Tests\Feature;

use Tests\TestCase;

class WebsiteTest extends TestCase
{
    public function test_homepage_shows_the_ministry_details_and_one_page_sections(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(config('website.slogan'))
            ->assertSee(config('website.contact.email'))
            ->assertSee('data-site-menu-toggle', false)->assertSee('aria-controls="site-menu"', false)
            ->assertSee('tel:'.config('website.contact.phone_primary.dial'), false)
            ->assertSee('id="about"', false)
            ->assertSee('id="work"', false)
            ->assertSee('id="contact"', false);
    }

    public function test_old_page_urls_redirect_to_homepage_sections(): void
    {
        foreach ([
            '/about' => '/#about',
            '/our-work' => '/#work',
            '/stories' => '/#stories',
            '/support' => '/#give',
            '/contact' => '/#contact',
        ] as $url => $section) {
            $this->get($url)->assertRedirect($section);
        }
    }

    public function test_country_giving_information_is_available_on_this_website(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('href="#donate-canada"', false)
            ->assertSee('href="#donate-usa"', false)
            ->assertSee('href="#donate-uk"', false)
            ->assertSee('id="donate-canada"', false)
            ->assertSee('id="donate-usa"', false)
            ->assertSee('id="donate-uk"', false)
            ->assertSee('info@mmf1985.org')
            ->assertSee('470250 Highway 95')
            ->assertSee('Mission International')
            ->assertSee(config('website.donations.canada_card_url'), false)
            ->assertSee(config('website.donations.usa_online_url'), false)
            ->assertSee(config('website.donations.uk_online_url'), false)
            ->assertDontSee('fathersheartmobility.org/donate', false);
    }
}
