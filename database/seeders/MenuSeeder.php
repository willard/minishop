<?php

namespace Database\Seeders;

use App\Enums\MenuLocation;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            MenuLocation::HeaderPrimary->value => [
                ['label' => 'Home', 'url' => '/', 'sort_order' => 1],
                ['label' => 'Shop', 'url' => '/products', 'sort_order' => 2],
                ['label' => 'Blog', 'url' => '/blog', 'sort_order' => 3],
                ['label' => 'About', 'url' => '/pages/about', 'sort_order' => 4],
                ['label' => 'Contact', 'url' => '/pages/contact', 'sort_order' => 5],
            ],
            MenuLocation::FooterShop->value => [
                ['label' => 'All Products', 'url' => '/products', 'sort_order' => 1],
                ['label' => 'Blog', 'url' => '/blog', 'sort_order' => 2],
            ],
            MenuLocation::FooterAbout->value => [
                ['label' => 'About Us', 'url' => '/pages/about', 'sort_order' => 1],
                ['label' => 'Contact', 'url' => '/pages/contact', 'sort_order' => 2],
                ['label' => 'FAQ', 'url' => '/pages/faq', 'sort_order' => 3],
            ],
            MenuLocation::FooterLegal->value => [
                ['label' => 'Privacy Policy', 'url' => '/pages/privacy', 'sort_order' => 1],
                ['label' => 'Terms of Service', 'url' => '/pages/terms', 'sort_order' => 2],
            ],
        ];

        foreach ($menus as $location => $items) {
            foreach ($items as $item) {
                MenuItem::query()->updateOrCreate(
                    ['menu_location' => $location, 'label' => $item['label']],
                    $item + ['menu_location' => $location, 'target' => '_self'],
                );
            }
        }
    }
}
