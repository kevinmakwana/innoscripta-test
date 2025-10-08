<?php

return [
    // Default page size for paginated API responses
    'per_page' => env('API_PER_PAGE', 15),

    // Maximum allowed page size to prevent very large responses
    'max_per_page' => env('API_MAX_PER_PAGE', 100),
];
