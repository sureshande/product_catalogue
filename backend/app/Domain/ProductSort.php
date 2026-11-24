<?php

namespace App\Domain;

enum ProductSort: string
{
    /** The source's natural order. */
    case Default = 'default';

    /** Highest rating first; ties broken by number of ratings, then id. */
    case Rating = 'rating';
}
