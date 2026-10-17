<?php

namespace App\Models;

/**
 * Play is a compatibility alias for the Show model in the canonical schema.
 */
class Play extends Show
{
    protected $table = 'shows';
}
