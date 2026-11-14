<?php

/*
 | Site-wide facts. Values the owner has not supplied yet are null and render as
 | "to be supplied" via the .tbs marker. Real values come from the M0 request.
 */
return [
    'name' => 'PG Foils Ltd',
    'staging_password' => env('STAGING_PASSWORD'),
    'canonical_url' => env('SITE_CANONICAL_URL', 'https://pgfoils.in'),
    'tagline' => 'Aluminium foil rolled, laminated and printed in Pipalia Kalan, Rajasthan.',

    'contact' => [
        'address' => 'P.O. Pipalia Kalan 306 307, Dist. Pali, Rajasthan',
        'phone' => '+91-2937-287151/52',
        'mobile' => '+91-97729-81111 / 97729-82222',
        'email' => 'pgfoils@pgfoils.in',
        'sales_inbox' => null, // M0: owner to confirm who receives quote requests
    ],

    // Primary navigation. One level of children, shown as a dropdown on desktop and a list in the phone menu.
    'nav' => [
        ['label' => 'Products', 'route' => 'products'],
        ['label' => 'Company', 'route' => 'company', 'children' => [
            ['label' => 'About and leadership', 'route' => 'company'],
            ['label' => 'Plants and machinery', 'route' => 'plants'],
            ['label' => 'Clients', 'route' => 'clients'],
            ['label' => 'Certificates', 'route' => 'certificates'],
        ]],
        ['label' => 'Sustainability', 'route' => 'sustainability'],
        ['label' => 'Investors', 'route' => 'investors'],
    ],

    // The one primary action. Shown in the header on desktop and pinned to the bottom on phones.
    'cta' => ['label' => 'Request a quote', 'route' => 'quote'],

    // Footer sitemap, so nothing is more than one click from any page.
    'footer' => [
        'Products' => [['Product range', 'products'], ['Request a quote', 'quote'], ['Certificates', 'certificates']],
        'Company' => [['About and leadership', 'company'], ['Plants and machinery', 'plants'], ['Clients', 'clients'], ['Sustainability', 'sustainability']],
        'Investors' => [['Investors Corner', 'investors'], ['Grievance management', 'grievance']],
        'Contact' => [['Contact the plant', 'contact']],
    ],
];
