<?php

namespace WalkerChiu\MorphNav\Models\Constants;

/**
 * @license MIT
 * @package WalkerChiu\MorphNav
 *
 *
 */

class MorphType
{
    /**
     * @param String  $type
     * @return Array
     */
    public static function getCodes(string $type): array
    {
        $items = [];
        $types = self::all();

        switch ($type) {
            case "relation":
                foreach ($types as $key => $value)
                    array_push($items, $key);
                break;
            case "class":
                foreach ($types as $value)
                    array_push($items, $value);
                break;
        }

        return $items;
    }

    /**
     * @param Bool  $onlyVaild
     * @return Array
     */
    public static function options($onlyVaild = false): array
    {
        $items = $onlyVaild ? [] : ['' => trans('php-core::system.null')];

        $types = self::all();
        foreach ($types as $key => $value) {
            $items = array_merge($items, [$key => trans('php-morph-nav::system.morphType.'.$key)]);
        }

        return $items;
    }

    /**
     * @return Array
     */
    public static function all(): array
    {
        return [
            'admin'      => 'Admin',
            'blog'       => 'Blog',
            'dashboard'  => 'Dashboard',
            'device'     => 'Device',
            'group'      => 'Group',
            'hq'         => 'Headquarter',
            'setting'    => 'Setting',
            'store'      => 'Store',
            'site'       => 'Site',
        ];
    }
}
