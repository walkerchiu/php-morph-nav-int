<?php

namespace WalkerChiu\MorphNav\Models\Entities;

use WalkerChiu\Core\Models\Entities\Lang;

class NavLang extends Lang
{
    /**
     * Create a new instance.
     *
     * @param Array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->table = config('wk-core.table.morph-nav.navs_lang');

        parent::__construct($attributes);
    }
}
