<?php

/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'name' => $faker->valid(function($name) {
        return mb_strlen($name) <= 50;
    })->name(),
    'code' => mt_rand(1, 100) <= 80 ? $faker->unique()->regexify('[a-z_][a-z0-9_]{2}') : null,
    't_status' => $faker->randomElement(['ok', 'hold']),
];
