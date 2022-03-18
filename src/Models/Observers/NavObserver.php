<?php

namespace WalkerChiu\MorphNav\Models\Observers;

class NavObserver
{
    /**
     * Handle the entity "retrieved" event.
     *
     * @param Entity  $entity
     * @return void
     */
    public function retrieved($entity)
    {
        //
    }

    /**
     * Handle the entity "creating" event.
     *
     * @param Entity  $entity
     * @return void
     */
    public function creating($entity)
    {
        //
    }

    /**
     * Handle the entity "created" event.
     *
     * @param Entity  $entity
     * @return void
     */
    public function created($entity)
    {
        //
    }

    /**
     * Handle the entity "updating" event.
     *
     * @param Entity  $entity
     * @return void
     */
    public function updating($entity)
    {
        //
    }

    /**
     * Handle the entity "updated" event.
     *
     * @param Entity  $entity
     * @return void
     */
    public function updated($entity)
    {
        //
    }

    /**
     * Handle the entity "saving" event.
     *
     * @param Entity  $entity
     * @return void
     */
    public function saving($entity)
    {
        if (
            !in_array($entity->identifier, ['#', '/'])
            && config('wk-core.class.morph-nav.nav')
                ::where('id', '<>', $entity->id)
                ->where('host_type', $entity->host_type)
                ->where('host_id', $entity->host_id)
                ->where('identifier', $entity->identifier)
                ->exists()
        )
            return false;
    }

    /**
     * Handle the entity "saved" event.
     *
     * @param Entity  $entity
     * @return void
     */
    public function saved($entity)
    {
        //
    }

    /**
     * Handle the entity "deleting" event.
     *
     * @param Entity  $entity
     * @return void
     */
    public function deleting($entity)
    {
        if (in_array($entity->identifier, config('wk-morph-nav.navs_protected')))
            return false;
    }

    /**
     * Handle the entity "deleted" event.
     *
     * Its Lang will be automatically removed by database.
     *
     * @param Entity  $entity
     * @return void
     */
    public function deleted($entity)
    {
        if ($entity->isForceDeleting()) {
            $entity->langs()->withTrashed()
                            ->forceDelete();
            if (
                config('wk-morph-nav.onoff.morph-board')
                && !empty(config('wk-core.class.morph-board.board'))
            ) {
                $records = $entity->boards()->withTrashed()->get();
                foreach ($records as $recoed) {
                    $recoed->forceDelete();
                }
            }
        }

        if (!config('wk-morph-nav.soft_delete')) {
            $entity->forceDelete();
        }
    }

    /**
     * Handle the entity "restoring" event.
     *
     * @param Entity  $entity
     * @return void
     */
    public function restoring($entity)
    {
        if (
            !in_array($entity->identifier, ['#', '/'])
            && config('wk-core.class.morph-nav.nav')
                ::where('id', '<>', $entity->id)
                ->where('host_type', $entity->host_type)
                ->where('host_id', $entity->host_id)
                ->where('identifier', $entity->identifier)
                ->exists()
        )
            return false;
    }

    /**
     * Handle the entity "restored" event.
     *
     * @param Entity  $entity
     * @return void
     */
    public function restored($entity)
    {
        //
    }
}
