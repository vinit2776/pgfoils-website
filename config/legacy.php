<?php

/*
 | Old addresses on pgfoils.co.in / pgfoils.co and where they go now. Used by routes/web.php to answer
 | with permanent (301) redirects so links in old filings, emails and search results keep working.
 */
return [
    // /reports/{id}/... on the old site -> [section slug, optional child slug, optional financial year]
    'reports' => [
        1 => ['annual-reports'], 5 => ['credit-rating'], 6 => ['corporate-governance'], 7 => ['mgt-7'], 8 => ['iepf'],
        9 => ['financial-results', null, '2022-23'], 10 => ['financial-results', null, '2021-22'], 11 => ['financial-results', null, '2020-21'],
        12 => ['financial-results', null, '2019-20'], 30 => ['financial-results', null, '2023-24'], 31 => ['financial-results', null, '2024-25'],
        32 => ['financial-results', null, '2025-26'],
        13 => ['shareholding', null, '2022-23'], 14 => ['shareholding', null, '2021-22'], 15 => ['shareholding', null, '2020-21'], 16 => ['shareholding', null, '2019-20'],
        17 => ['announcements', 'intimation'], 18 => ['announcements', 'outcome'], 19 => ['announcements', 'voting-results'],
        20 => ['announcements', 'newspaper-publication'], 21 => ['announcements', 'related-party-disclosure'], 22 => ['announcements', 'other-disclosure'],
        29 => ['reg-46-62'],
    ],

    // Old pages -> route names
    'pages' => [
        'index.html' => 'home', 'products.html' => 'products', 'plants.html' => 'plants', 'global-presence.html' => 'company',
        'green-initiative.html' => 'sustainability', 'csr.html' => 'sustainability', 'grivances-management.html' => 'grievance',
        'news.html' => 'home', 'contact.html' => 'contact', 'about.html' => 'company', 'clients.html' => 'clients',
    ],

    // Old fixed files -> new files
    'files' => [
        '/storage/products/product-specifications.pdf' => '/docs/product-specifications.pdf',
    ],
];
