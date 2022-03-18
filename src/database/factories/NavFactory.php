<?php

/** @var \Illuminate\Database\Eloquent\Factory  $factory */

use Faker\Generator as Faker;
use WalkerChiu\MorphNav\Models\Entities\Nav;
use WalkerChiu\MorphNav\Models\Entities\NavLang;

$factory->define(Nav::class, function (Faker $faker) {
    return [
        'serial'     => $faker->isbn10,
        'identifier' => $faker->slug,
        'order'      => $faker->randomNumber
    ];
});

$factory->define(NavLang::class, function (Faker $faker) {
    return [
        'code'  => $faker->locale,
        'key'   => $faker->randomElement(['name', 'description']),
        'value' => $faker->sentence
    ];
});
