<?php

return [
    'csp' => [
        'default_src' => "'self'",
        'script_src' => "'self'",
        'style_src' => "'self' 'unsafe-inline'",
        'img_src' => "'self' data: https:",
        'font_src' => "'self' https://fonts.gstatic.com",
        'connect_src' => "'self' https://www.googleapis.com https://api.linkedin.com wss: ws:",
        'frame_src' => 'https://www.youtube.com',
        'frame_ancestors' => "'none'",
    ],
    'hsts' => [
        'max_age' => 31536000,
        'include_subdomains' => true,
    ],
];
