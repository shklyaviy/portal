<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\SeoTemplate;
use App\Models\User;
use App\Services\OneC\CatalogSyncService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@portalfirma.local'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
            ]
        );

        SeoTemplate::query()->updateOrCreate(
            ['key' => 'product'],
            [
                'name' => 'Товар',
                'title_template' => '{name} купить — {category}',
                'description_template' => '{name}. Артикул {sku}. Цена {price} ₽.',
                'h1_template' => '{name}',
                'is_active' => true,
            ]
        );

        SeoTemplate::query()->updateOrCreate(
            ['key' => 'category'],
            [
                'name' => 'Категория',
                'title_template' => '{name} — каталог',
                'description_template' => 'Раздел каталога: {name}.',
                'h1_template' => '{name}',
                'is_active' => true,
            ]
        );

        Page::query()->updateOrCreate(
            ['slug' => 'about'],
            [
                'title' => 'О компании',
                'body_html' => '<p>Кровельный центр «Портал» — материалы для кровли и фасада.</p>',
                'is_published' => true,
            ]
        );

        Page::query()->updateOrCreate(
            ['slug' => 'contacts'],
            [
                'title' => 'Контакты',
                'body_html' => '<p>Новороссийск</p><p><a href="tel:+78617278000">8 (8617) 27-80-00</a></p>',
                'is_published' => true,
            ]
        );

        $payload = [
            'priceTypes' => [
                ['externalId' => 'retail', 'name' => 'Розничная', 'isDefault' => true],
                ['externalId' => 'wholesale', 'name' => 'Оптовая', 'isDefault' => false],
            ],
            'categories' => [
                [
                    'externalId' => 'cat-krovlya',
                    'name' => 'Кровля',
                    'slug' => 'krovlya',
                    'description' => 'Кровельные материалы (синхронизация из 1С, пилот)',
                ],
                [
                    'externalId' => 'cat-metallocherepitsa',
                    'name' => 'Металлочерепица',
                    'slug' => 'krovlya/metallocherepitsa',
                    'parentExternalId' => 'cat-krovlya',
                    'description' => 'Металлочерепица — пилотная группа из 1С',
                ],
                [
                    'externalId' => 'cat-profnastil',
                    'name' => 'Профнастил',
                    'slug' => 'krovlya/profnastil',
                    'parentExternalId' => 'cat-krovlya',
                ],
            ],
            'products' => [
                [
                    'externalId' => '1c-mt-monterrey-05-ral8017',
                    'name' => 'Металлочерепица Monterrey 0.5 мм RAL 8017',
                    'sku' => 'MT-MONT-05-8017',
                    'slug' => 'krovlya/metallocherepitsa/monterrey-05-ral-8017',
                    'categoryExternalId' => 'cat-metallocherepitsa',
                    'description' => 'Пилотная позиция из 1С. Толщина 0.5 мм, покрытие полиэстер, цвет RAL 8017.',
                    'price' => 689,
                    'currency' => 'RUB',
                    'unit' => 'м²',
                    'attributes' => [
                        ['externalId' => 'attr-thickness', 'name' => 'Толщина', 'value' => '0.5 мм'],
                        ['externalId' => 'attr-coating', 'name' => 'Покрытие', 'value' => 'Полиэстер'],
                        ['externalId' => 'attr-ral', 'name' => 'Цвет RAL', 'value' => '8017'],
                        ['name' => 'Профиль', 'value' => 'Monterrey'],
                    ],
                    'prices' => [
                        ['priceTypeExternalId' => 'retail', 'amount' => 689, 'currency' => 'RUB'],
                        ['priceTypeExternalId' => 'wholesale', 'amount' => 640, 'currency' => 'RUB'],
                    ],
                ],
                [
                    'externalId' => '1c-mt-cascad-045-ral6005',
                    'name' => 'Металлочерепица Cascad 0.45 мм RAL 6005',
                    'sku' => 'MT-CASC-045-6005',
                    'slug' => 'krovlya/metallocherepitsa/cascad-045-ral-6005',
                    'categoryExternalId' => 'cat-metallocherepitsa',
                    'description' => 'Пилотная позиция. Толщина 0.45 мм, цвет зелёный мох.',
                    'price' => 612,
                    'unit' => 'м²',
                    'attributes' => [
                        ['name' => 'Толщина', 'value' => '0.45 мм'],
                        ['name' => 'Покрытие', 'value' => 'Полиэстер'],
                        ['name' => 'Цвет RAL', 'value' => '6005'],
                    ],
                    'prices' => [
                        ['priceTypeExternalId' => 'retail', 'amount' => 612],
                    ],
                ],
                [
                    'externalId' => '1c-c8-a-07-ral9003',
                    'name' => 'Профнастил С-8 0.7 мм RAL 9003',
                    'sku' => 'C8-07-9003',
                    'slug' => 'krovlya/profnastil/c8-07-ral-9003',
                    'categoryExternalId' => 'cat-profnastil',
                    'description' => 'Стеновой/кровельный профнастил С-8.',
                    'price' => 545,
                    'unit' => 'м²',
                    'attributes' => [
                        ['name' => 'Толщина', 'value' => '0.7 мм'],
                        ['name' => 'Профиль', 'value' => 'С-8'],
                        ['name' => 'Цвет RAL', 'value' => '9003'],
                    ],
                    'prices' => [
                        ['priceTypeExternalId' => 'retail', 'amount' => 545],
                        ['priceTypeExternalId' => 'wholesale', 'amount' => 510],
                    ],
                ],
            ],
        ];

        app(CatalogSyncService::class)->upsertCatalog($payload);
    }
}
