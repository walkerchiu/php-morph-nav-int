<?php

namespace WalkerChiu\MorphNav\Models\Entities;

use WalkerChiu\Core\Models\Entities\Entity;
use WalkerChiu\Core\Models\Entities\LangTrait;

class Nav extends Entity
{
    use LangTrait;



    /**
     * Create a new instance.
     *
     * @param Array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->table = config('wk-core.table.morph-nav.navs');

        $this->fillable = array_merge($this->fillable, [
            'host_type', 'host_id',
            'ref_id',
            'type',
            'serial',
            'identifier',
            'url',
            'target',
            'icon',
            'order'
        ]);

        parent::__construct($attributes);
    }

    /**
     * Get it's lang entity.
     *
     * @return Lang
     */
    public function lang()
    {
        if (
            config('wk-core.onoff.core-lang_core')
            || config('wk-morph-nav.onoff.core-lang_core')
        ) {
            return config('wk-core.class.core.langCore');
        } else {
            return config('wk-core.class.morph-nav.navLang');
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function langs()
    {
        if (
            config('wk-core.onoff.core-lang_core')
            || config('wk-morph-nav.onoff.core-lang_core')
        ) {
            return $this->langsCore();
        } else {
            return $this->hasMany(config('wk-core.class.morph-nav.navLang'), 'morph_id', 'id');
        }
    }

    /**
     * Get the owning host model.
     */
    public function host()
    {
        return $this->morphTo();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reference()
    {
        return $this->belongsTo(self::class, 'ref_id', 'id');
    }

    /**
     * Get the owning parent model.
     */
    public function parent()
    {
        if (empty($this->ref_id))
            return $this->host;
        else
            return $this->reference;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function roles()
    {
        return $this->morphMany(config('wk-core.class.role.role'), 'morph');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphedByMany
     */
    public function stocks()
    {
        return $this->morphedByMany(config('wk-core.class.mall-shelf.stock'), 'morph', config('wk-core.table.morph-nav.navs_morphs'));
    }

    /**
     * @param String  $type
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function boards($type = null)
    {
        return $this->morphMany(config('wk-core.class.morph-board.board'), 'host')
                    ->when($type, function ($query, $type) {
                                return $query->where('type', $type);
                            });
    }

    /**
     * Get all of the navs for the nav.
     *
     * @param String  $type
     * @param Bool    $is_enabled
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function navs($type = null, $is_enabled = null)
    {
        return $this->hasMany(config('wk-core.class.morph-nav.nav'), 'ref_id', 'id')
                    ->when($type, function ($query, $type) {
                                return $query->where( function ($query) use ($type) {
                                    return $query->whereNull('type')
                                                 ->orWhere('type', $type);
                                });
                            })
                    ->unless( is_null($is_enabled), function ($query) use ($is_enabled) {
                        return $query->where('is_enabled', $is_enabled);
                    });
    }

    /**
     * Check if it belongs to the user.
     * 
     * @param User  $user
     * @return Bool
     */
    public function isOwnedBy($user): bool
    {
        if (empty($user))
            return false;

        return $this->host->user_id == $user->id;
    }
}
