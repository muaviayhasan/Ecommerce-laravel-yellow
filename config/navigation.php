<?php

/*
|--------------------------------------------------------------------------
| Admin Sidebar Navigation (CONVENTIONS §8)
|--------------------------------------------------------------------------
| The admin sidebar renders from this tree (see x-admin.sidebar). Each module
| adds its entry here when its screen is built. Item shape:
|
|   ['heading' => 'Catalog']                              // section label
|   [
|     'label'      => 'Products',
|     'icon'       => 'shopping_cart',                     // Material Symbols name
|     'route'      => 'admin.products.index',              // named route (optional)
|     'active'     => 'admin.products.*',                  // routeIs() pattern (optional)
|     'permission' => 'products.view',                     // gate (optional)
|     'children'   => [ ...same shape, no icon... ],       // collapsible group
|   ]
|
| Items whose route is not registered yet render as inert placeholders (href="#")
| so the nav can list upcoming modules without 500s. Permission-gated items are
| hidden from users who lack the permission.
*/

return [
    'admin' => [
        ['heading' => 'Main Home'],
        [
            'label' => 'Dashboard',
            'icon' => 'dashboard',
            'route' => 'admin.dashboard',
            'active' => 'admin.dashboard',
            'permission' => 'dashboard.view',
        ],

        ['heading' => 'All Pages'],
        [
            'label' => 'Point of Sale',
            'icon' => 'point_of_sale',
            'route' => 'admin.pos.index',
            'active' => 'admin.pos.*',
            'permission' => 'pos.access',
        ],
        [
            'label' => 'Vendor Sale',
            'icon' => 'sell',
            'route' => 'admin.vendor-sales.index',
            'active' => 'admin.vendor-sales.*',
            'permission' => 'orders.create',
        ],
        [
            'label' => 'Ecommerce',
            'icon' => 'shopping_cart',
            'children' => [
                [
                    'label' => 'Products',
                    'route' => 'admin.products.index',
                    'active' => 'admin.products.*',
                    'permission' => 'products.view',
                ],
                [
                    'label' => 'Categories',
                    'route' => 'admin.categories.index',
                    'active' => 'admin.categories.*',
                    'permission' => 'categories.view',
                ],
                [
                    'label' => 'Brands',
                    'route' => 'admin.brands.index',
                    'active' => 'admin.brands.*',
                    'permission' => 'brands.view',
                ],
                [
                    'label' => 'Attributes',
                    'route' => 'admin.attributes.index',
                    'active' => 'admin.attributes.*',
                    'permission' => 'attributes.view',
                ],
                [
                    'label' => 'Orders',
                    'route' => 'admin.orders.index',
                    'active' => 'admin.orders.*',
                    'permission' => 'orders.view',
                ],
                [
                    'label' => 'Quotations',
                    'route' => 'admin.quotations.index',
                    'active' => 'admin.quotations.*',
                    'permission' => 'quotations.view',
                ],
                [
                    'label' => 'Coupons',
                    'route' => 'admin.coupons.index',
                    'active' => 'admin.coupons.*',
                    'permission' => 'coupons.view',
                ],
                [
                    'label' => 'Reviews',
                    'route' => 'admin.reviews.index',
                    'active' => 'admin.reviews.*',
                    'permission' => 'reviews.view',
                ],
                [
                    'label' => 'Customers',
                    'route' => 'admin.customers.index',
                    'active' => 'admin.customers.*',
                    'permission' => 'customers.view',
                ],
            ],
        ],
        [
            'label' => 'Procurement',
            'icon' => 'local_shipping',
            'children' => [
                [
                    'label' => 'Suppliers',
                    'route' => 'admin.suppliers.index',
                    'active' => 'admin.suppliers.*',
                    'permission' => 'suppliers.view',
                ],
                [
                    'label' => 'Purchases',
                    'route' => 'admin.purchases.index',
                    'active' => 'admin.purchases.*',
                    'permission' => 'purchases.view',
                ],
                [
                    'label' => 'Inventory',
                    'route' => 'admin.inventory.index',
                    'active' => 'admin.inventory.*',
                    'permission' => 'stock.view',
                ],
            ],
        ],
        [
            'label' => 'Manufacturing',
            'icon' => 'precision_manufacturing',
            'children' => [
                [
                    'label' => 'BOMs',
                    'route' => 'admin.boms.index',
                    'active' => 'admin.boms.*',
                    'permission' => 'boms.view',
                ],
                [
                    'label' => 'Production',
                    'route' => 'admin.production.index',
                    'active' => 'admin.production.*',
                    'permission' => 'production.view',
                ],
            ],
        ],
        [
            'label' => 'Blog',
            'icon' => 'article',
            'children' => [
                [
                    'label' => 'Posts',
                    'route' => 'admin.blog.posts.index',
                    'active' => 'admin.blog.posts.*',
                    'permission' => 'blog-posts.view',
                ],
                [
                    'label' => 'Categories',
                    'route' => 'admin.blog.categories.index',
                    'active' => 'admin.blog.categories.*',
                    'permission' => 'blog-categories.view',
                ],
                [
                    'label' => 'Tags',
                    'route' => 'admin.blog.tags.index',
                    'active' => 'admin.blog.tags.*',
                    'permission' => 'blog-tags.view',
                ],
            ],
        ],
        [
            'label' => 'Users',
            'icon' => 'group',
            'route' => 'admin.users.index',
            'active' => 'admin.users.*',
            'permission' => 'users.view',
        ],
        [
            'label' => 'Gallery',
            'icon' => 'image',
            'route' => 'admin.gallery.index',
            'active' => 'admin.gallery.*',
            'permission' => 'gallery.view',
        ],
        [
            'label' => 'Reports',
            'icon' => 'bar_chart',
            'route' => 'admin.reports.index',
            'active' => 'admin.reports.*',
            'permission' => 'reports.view',
        ],
        [
            'label' => 'Ledger',
            'icon' => 'account_balance',
            'route' => 'admin.ledger.index',
            'active' => 'admin.ledger.*',
            'permission' => 'ledger.view',
        ],

        ['heading' => 'Settings'],
        [
            'label' => 'Roles & Permissions',
            'icon' => 'admin_panel_settings',
            'route' => 'admin.roles.index',
            'active' => 'admin.roles.*',
            'permission' => 'roles.view',
        ],
        [
            'label' => 'Activity Log',
            'icon' => 'history',
            'route' => 'admin.activity.index',
            'active' => 'admin.activity.*',
            'permission' => 'audit.view',
        ],
        [
            'label' => 'Settings',
            'icon' => 'settings',
            'route' => 'admin.settings.index',
            'active' => 'admin.settings.*',
            'permission' => 'settings.view',
        ],
    ],
];
