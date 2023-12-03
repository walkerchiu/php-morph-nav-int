<?php

namespace WalkerChiu\MorphNav\Models\Entities;

use WalkerChiu\MorphNav\Models\Entities\Nav;
use WalkerChiu\MorphImage\Models\Entities\ImageTrait;
use WalkerChiu\MorphLink\Models\Entities\LinkTrait;

class NavWithImageAndLink extends Nav
{
    use ImageTrait;
    use LinkTrait;
}
