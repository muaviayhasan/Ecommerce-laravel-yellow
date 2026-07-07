<?php

/*
|--------------------------------------------------------------------------
| Admin Documentation (in-app handbook)
|--------------------------------------------------------------------------
| Drives the Documentation section (Admin → Help → Documentation). Each page
| maps to a Blade partial at resources/views/admin/docs/pages/{slug}.blade.php.
| The controller builds the table of contents, prev/next links and validates
| the requested slug from this manifest — add a page by adding an entry here
| plus its partial. Keep the order meaningful: the first page is the landing.
*/

return [

    'groups' => [
        [
            'label' => 'Getting started',
            'pages' => [
                'overview' => [
                    'title' => 'System overview',
                    'icon' => 'lightbulb',
                    'summary' => 'What this platform is, who uses it, and the big picture.',
                ],
                'architecture' => [
                    'title' => 'Architecture & tech stack',
                    'icon' => 'account_tree',
                    'summary' => 'The layers a request passes through and the tools behind them.',
                ],
                'conventions' => [
                    'title' => 'Engineering conventions',
                    'icon' => 'rule',
                    'summary' => 'The rules every module in this codebase follows.',
                ],
            ],
        ],
        [
            'label' => 'How it all connects',
            'pages' => [
                'module-map' => [
                    'title' => 'Module relationship map',
                    'icon' => 'hub',
                    'summary' => 'Which module depends on which — the wiring of the whole system.',
                ],
                'services' => [
                    'title' => 'The service layer',
                    'icon' => 'settings_suggest',
                    'summary' => 'The shared engines that every money- or stock-moving action runs through.',
                ],
                'money-flow' => [
                    'title' => 'Financial flow (ledger)',
                    'icon' => 'account_balance',
                    'summary' => 'How sales, purchases and production post to a double-entry ledger.',
                ],
                'inventory-flow' => [
                    'title' => 'Stock & costing flow',
                    'icon' => 'inventory',
                    'summary' => 'The lifecycle of a unit of stock and how its cost is calculated.',
                ],
            ],
        ],
        [
            'label' => 'Modules',
            'pages' => [
                'catalog' => [
                    'title' => 'Catalog & storefront content',
                    'icon' => 'shopping_bag',
                    'summary' => 'Products, variants, categories, brands, attributes, gallery, home-page content.',
                ],
                'sales' => [
                    'title' => 'Sales & orders',
                    'icon' => 'point_of_sale',
                    'summary' => 'Checkout, POS, vendor sales, quotations, coupons and the order lifecycle.',
                ],
                'procurement' => [
                    'title' => 'Procurement & inventory',
                    'icon' => 'local_shipping',
                    'summary' => 'Suppliers, purchase orders, receiving and stock levels.',
                ],
                'manufacturing' => [
                    'title' => 'Manufacturing',
                    'icon' => 'precision_manufacturing',
                    'summary' => 'Bills of materials and production runs that consume and produce stock.',
                ],
                'crm-support' => [
                    'title' => 'Customers, reviews & support',
                    'icon' => 'groups',
                    'summary' => 'Customer records, review moderation and the live support inbox.',
                ],
                'marketing' => [
                    'title' => 'Marketing & email',
                    'icon' => 'campaign',
                    'summary' => 'Campaigns, subscribers and abandoned-cart recovery.',
                ],
                'blog' => [
                    'title' => 'Blog & content',
                    'icon' => 'article',
                    'summary' => 'Posts, categories, tags and comment moderation.',
                ],
                'finance' => [
                    'title' => 'Finance & reporting',
                    'icon' => 'bar_chart',
                    'summary' => 'The ledger view and the analytics/reporting dashboard.',
                ],
                'system' => [
                    'title' => 'System, users & security',
                    'icon' => 'shield',
                    'summary' => 'Settings, staff users, roles, activity log and error logs.',
                ],
            ],
        ],
        [
            'label' => 'Reference',
            'pages' => [
                'permissions' => [
                    'title' => 'Roles & permissions',
                    'icon' => 'admin_panel_settings',
                    'summary' => 'Every role, every permission and who can do what.',
                ],
                'storefront' => [
                    'title' => 'Storefront map',
                    'icon' => 'storefront',
                    'summary' => 'The public website and how each page draws on admin data.',
                ],
            ],
        ],
    ],

];
