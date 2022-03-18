<?php

namespace WalkerChiu\MorphNav\Models\Repositories;

use Illuminate\Support\Facades\App;
use WalkerChiu\Core\Models\Forms\FormHasHostTrait;
use WalkerChiu\Core\Models\Repositories\Repository;
use WalkerChiu\Core\Models\Repositories\RepositoryHasHostTrait;
use WalkerChiu\Core\Models\Services\PackagingFactory;

class NavRepository extends Repository
{
    use FormHasHostTrait;
    use RepositoryHasHostTrait;

    protected $instance;
    protected $morphType;



    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->instance  = App::make(config('wk-core.class.morph-nav.nav'));
        $this->morphType = App::make(config('wk-core.class.morph-nav.morphType'))::getCodes('relation');
    }

    /**
     * @param String  $host_type
     * @param Int     $host_id
     * @param String  $code
     * @param Array   $data
     * @param Bool    $is_enabled
     * @param String  $target
     * @param Bool    $target_is_enabled
     * @param Array   $exceptData
     * @param Bool    $auto_packing
     * @return Array|Collection|Eloquent
     */
    public function list(?string $host_type, ?int $host_id, string $code, array $data, $is_enabled = null, $target = null, $target_is_enabled = null, $exceptData = [], $auto_packing = false)
    {
        if (
            empty($host_type)
            || empty($host_id)
        ) {
            $instance = $this->instance;
        } else {
            $instance = $this->baseQueryForRepository($host_type, $host_id, $target, $target_is_enabled);
        }
        if ($is_enabled === true)      $instance = $instance->ofEnabled();
        elseif ($is_enabled === false) $instance = $instance->ofDisabled();

        $data = array_map('trim', $data);
        $repository = $instance->with(['langs' => function ($query) use ($code) {
                                    $query->ofCurrent()
                                          ->ofCode($code);
                                }])
                                ->whereHas('langs', function ($query) use ($code) {
                                    return $query->ofCurrent()
                                                 ->ofCode($code);
                                })
                                ->when(
                                    config('wk-morph-nav.onoff.morph-tag')
                                    && !empty(config('wk-core.class.morph-tag.tag'))
                                    , function ($query) {
                                        return $query->with(['tags', 'tags.langs']);
                                })
                                ->when($exceptData, function ($query, $exceptData) {
                                    return $query->where( function ($query) use ($exceptData) {
                                        return $query->whereNull('type')
                                                     ->orWhereNotIn('type', $exceptData);
                                    });
                                })
                                ->when($data, function ($query, $data) {
                                    return $query->when(empty($data['id']), function ($query) use ($data) {
                                                    return $query->whereNull('ref_id');
                                                }, function ($query) use ($data) {
                                                    return $query->where('ref_id', $data['id']);
                                                })
                                            ->unless(empty($data['type']), function ($query) use ($data) {
                                                return $query->where('type', $data['type']);
                                            })
                                            ->unless(empty($data['serial']), function ($query) use ($data) {
                                                return $query->where('serial', $data['serial']);
                                            })
                                            ->unless(empty($data['identifier']), function ($query) use ($data) {
                                                return $query->where('identifier', $data['identifier']);
                                            })
                                            ->unless(empty($data['order']), function ($query) use ($data) {
                                                return $query->where('order', $data['order']);
                                            })
                                            ->unless(empty($data['name']), function ($query) use ($data) {
                                                return $query->whereHas('langs', function ($query) use ($data) {
                                                    $query->ofCurrent()
                                                          ->where('key', 'name')
                                                          ->where('value', 'LIKE', "%".$data['name']."%");
                                                });
                                            })
                                            ->unless(empty($data['description']), function ($query) use ($data) {
                                                return $query->whereHas('langs', function ($query) use ($data) {
                                                    $query->ofCurrent()
                                                          ->where('key', 'description')
                                                          ->where('value', 'LIKE', "%".$data['description']."%");
                                                });
                                            });
                                })
                                ->orderBy('order', 'ASC');

        if ($auto_packing) {
            $factory = new PackagingFactory(config('wk-morph-nav.output_format'), config('wk-morph-nav.pagination.pageName'), config('wk-morph-nav.pagination.perPage'));
            $factory->setFieldsLang(['name', 'description']);

            if (in_array(config('wk-morph-nav.output_format'), ['array', 'array_pagination'])) {
                switch (config('wk-morph-nav.output_format')) {
                    case "array":
                        $entities = $factory->toCollection($repository);
                        // no break
                    case "array_pagination":
                        $entities = $factory->toCollectionWithPagination($repository);
                        // no break
                    default:
                        $output = [];
                        foreach ($entities as $instance) {
                            $data = $instance->toArray();
                            array_push($output,
                                array_merge($data, [
                                    'child_disabled' => $instance->navs(null, 0)->count(),
                                    'child_enabled'  => $instance->navs(null, 1)->count()
                                ])
                            );
                        }
                }
                return $output;
            } else {
                return $factory->output($repository);
            }
        }

        return $repository;
    }

    /**
     * @param String  $host_type
     * @param Int     $host_id
     * @param String  $code
     * @param String  $code_default
     * @param String  $type
     * @param Int     $id
     * @param Int     $degree
     * @return Array
     */
    public function listOption(?string $host_type, ?int $host_id, string $code, string $code_default, $type = null, $id = null, $degree = 0): array
    {
        if (
            empty($host_type)
            || empty($host_id)
        ) {
            $instance = $this->instance;
        } else {
            $instance = $this->baseQueryForRepository($host_type, $host_id);
        }
        $records = $instance->with(['langs' => function ($query) {
                                    $query->ofCurrent();
                                }])
                            ->ofEnabled()
                            ->when(is_null($type), function ($query) {
                                    return $query->whereNull('type');
                                }, function ($query) use ($type) {
                                    return $query->where('type', $type);
                                })
                            ->when(empty($id), function ($query) {
                                    return $query->whereNull('ref_id');
                                }, function ($query) use ($id) {
                                    return $query->where('ref_id', $id);
                                })
                            ->orderBy('order', 'ASC')
                            ->select('id', 'serial', 'identifier')
                            ->get();
        $list = [];
        foreach ($records as $record) {
            $name        = $record->findLang($code, 'name');
            $description = $record->findLang($code, 'description');
            if (empty($name)) {
                $name        = $record->findLang($code_default, 'name');
                $description = $record->findLang($code_default, 'description');
            }
            $list[$record->id] = [
                'serial'      => $record->serial,
                'identifier'  => $record->identifier,
                'name'        => $name,
                'description' => $description
            ];
            if ($degree > 0)
                $list[$record->id]['child'] = $this->listOption($host_type, $host_id, $code, $code_default, $type, $record->id, $degree-1);
        }

        return $list;
    }

    /**
     * @param String  $host_type
     * @param Int     $host_id
     * @param String  $code
     * @param String  $code_default
     * @param String  $type
     * @param Int     $id
     * @param Int     $degree
     * @param String  $target
     * @param Bool    $target_is_enabled
     * @return Array
     *
     * @throws NotUnsignedIntegerException
     */
    public function listMenu(?string $host_type, ?int $host_id, string $code, string $code_default, $type = null, $id = null, $degree = 0, $target = null, $target_is_enabled = null): array
    {
        if (
            !is_integer($degree)
            || $degree < 0
        ) {
            throw new NotUnsignedIntegerException($degree);
        }

        if (
            empty($host_type)
            || empty($host_id)
        ) {
            $instance = $this->instance;
        } else {
            $instance = $this->baseQueryForRepository($host_type, $host_id, $target, $target_is_enabled);
        }
        $records = $instance->with(['langs' => function ($query) {
                                    $query->ofCurrent();
                                }])
                            ->ofEnabled()
                            ->when(is_null($type), function ($query) {
                                    return $query->whereNull('type');
                                }, function ($query) use ($type) {
                                    return $query->where('type', $type);
                                })
                            ->when(empty($id), function ($query) {
                                    return $query->whereNull('ref_id');
                                }, function ($query) use ($id) {
                                    return $query->where('ref_id', $id);
                                })
                            ->orderBy('order', 'ASC')
                            ->select('id', 'serial', 'identifier', 'url', 'target', 'icon', 'order')
                            ->get();
        $list = [];
        foreach ($records as $record) {
            $name        = $record->findLang($code, 'name');
            $description = $record->findLang($code, 'description');
            if (empty($name)) {
                $name        = $record->findLang($code_default, 'name');
                $description = $record->findLang($code_default, 'description');
                if (empty($name))
                    continue;
            }
            $data = [
                'id'          => $record->id,
                'serial'      => $record->serial,
                'identifier'  => $record->identifier,
                'url'         => $record->url,
                'target'      => $record->target,
                'icon'        => $record->icon,
                'order'       => $record->order,
                'name'        => $name,
                'description' => $description
            ];
            if ($degree > 0)
                $data['child'] = $this->listMenu($host_type, $host_id, $code, $code_default, $type, $record->id, $degree-1, $target, $target_is_enabled);

            array_push($list, $data);
        }

        return $list;
    }

    /**
     * @param Int     $id
     * @param String  $code
     * @param String  $code_default
     * @param Array   $data
     * @return Array
     */
    public function listBreadcrumb(int $id, string $code, $code_default = null, $data = []): array
    {
        $instance = $this->find($id);

        if (is_null($code_default))
            $code_default = config('wk-core.language');

        if ($instance->reference) {
            $data = $this->listBreadcrumb($instance->reference->id, $code, $code_default, $data);
        }

        $name        = $instance->findLang($code, 'name');
        $description = $instance->findLang($code, 'description');
        if (empty($name)) {
            $name        = $instance->findLang($code_default, 'name');
            $description = $instance->findLang($code_default, 'description');
        }

        array_push($data, [
            'id'          => $instance->id,
            'identifier'  => $instance->identifier,
            'url'         => $instance->url,
            'icon'        => $instance->icon,
            'name'        => $name,
            'description' => $description
        ]);

        return $data;
    }

    /**
     * @param Nav      $instance
     * @param String|Array  $code
     * @param String        $code_default
     * @return Array
     */
    public function show($instance, $code, $code_default = null): array
    {
        $data = [
            'id'    => $instance ? $instance->id : '',
            'basic' => []
        ];

        if (empty($instance))
            return $data;

        $this->setEntity($instance);

        if (is_string($code)) {
            $data['basic'] = [
                'host_type'   => $instance->host_type,
                'host_id'     => $instance->host_id,
                'type'        => $instance->type,
                'ref_id'      => $instance->ref_id,
                'ref_name'    => $instance->ref_id ? $instance->reference->findLang($code, 'name') : '',
                'serial'      => $instance->serial,
                'identifier'  => $instance->identifier,
                'url'         => $instance->url,
                'order'       => $instance->order,
                'name'        => $instance->findLang($code, 'name'),
                'description' => $instance->findLang($code, 'description'),
                'target'      => $instance->target,
                'icon'        => $instance->icon,
                'is_enabled'  => $instance->is_enabled,
                'updated_at'  => $instance->updated_at,
                'breadcrumb'  => $this->listBreadcrumb($instance->id, $code, $code_default)
            ];

        } elseif (is_array($code)) {
            foreach ($code as $language) {
                $data['basic'][$language] = [
                    'host_type'   => $instance->host_type,
                    'host_id'     => $instance->host_id,
                    'type'        => $instance->type,
                    'ref_id'      => $instance->ref_id,
                    'ref_name'    => $instance->ref_id ? $instance->reference->findLang($language, 'name') : '',
                    'serial'      => $instance->serial,
                    'identifier'  => $instance->identifier,
                    'url'         => $instance->url,
                    'order'       => $instance->order,
                    'name'        => $instance->findLang($language, 'name'),
                    'description' => $instance->findLang($language, 'description'),
                    'target'      => $instance->target,
                    'icon'        => $instance->icon,
                    'is_enabled'  => $instance->is_enabled,
                    'updated_at'  => $instance->updated_at,
                    'breadcrumb'  => $this->listBreadcrumb($instance->id, $language, $code_default)
                ];
            }
        }

        return $data;
    }
}
