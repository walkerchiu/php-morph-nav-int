<?php

namespace WalkerChiu\MorphNav\Models\Services;

use Illuminate\Support\Facades\App;
use WalkerChiu\Core\Models\Services\CheckExistTrait;

class NavService
{
    use CheckExistTrait;

    protected $repository;



    /**
     * Create a new service instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->repository = App::make(config('wk-core.class.morph-nav.navRepository'));
    }

    /**
     * Insert default nav
     *
     * @param Array  $data_basic
     * @param Array  $data_lang
     * @return Nav
     */
    public function insertDefaultNav(array $data_basic, array $data_lang)
    {
        $nav = $this->repository->save($data_basic);

        foreach ($data_lang as $lang) {
            $lang['morph_type'] = get_class($nav);
            $lang['morph_id']   = $nav->id;
            $this->repository->createLangWithoutCheck($lang);
        }

        return $nav;
    }

    /**
     * @param String  $host_type
     * @param Int     $host_id
     * @param String  $code
     * @param String  $code_default
     * @param String  $type
     * @param String  $id
     * @param Int     $degree
     * @return Array
     */
    public function listOption(?string $host_type, ?int $host_id, string $code, string $code_default, $type = null, $id = null, $degree = 0): array
    {
        return $this->repository->listOption($host_type, $host_id, $code, $code_default, $type, $id, $degree);
    }

    /**
     * @param Nav     $nav
     * @param String  $code
     * @param Bool    $include
     * @return Array
     */
    public function loadNavPath($nav, $code = null, $include = false): array
    {
        if (empty($nav))
            return [];
        if (empty($code))
            $code = config('app.locale');

        $path = [];
        while (true) {
            if ($include) {
                array_push($path, [
                    'id'   => $nav->id,
                    'name' => $nav->findLang($code, 'name')
                ]);
                if (empty($nav->ref_id))
                    break;
                $nav = $nav->parent();
            } else {
                if (empty($nav->ref_id))
                    break;
                $nav = $nav->parent();
                array_push($path, [
                    'id'   => $nav->id,
                    'name' => $nav->findLang($code, 'name')
                ]);
            }
        }
        return array_reverse($path);
    }

    /**
     * @param Nav     $nav
     * @param String  $code
     * @return String
     */
    public function loadNavPathText($nav, $code = null): string
    {
        if (empty($nav))
            return '';
        if (empty($code))
            $code = config('app.locale');

        $path = [$nav->findLang($code, 'name') . ' / '];
        while (true) {
            if (empty($nav->ref_id))
                break;
            $nav = $nav->parent();
            $name = $nav->findLang($code, 'name') . ' / ';
            array_push($path, $name);
        }
        return implode('', array_reverse($path));
    }

    /**
     * @param Bool    $isOwner
     * @param Nav     $record
     * @param String  $code
     * @param Bool    $transform
     * @return Array
     */
    public function loadParentOptions(bool $isOwner, $record, $code = null, $transform = true): array
    {
        if (empty($code))
            $code = config('app.locale');

        $parent = $record->parent();
        if (is_a($parent, config('wk-core.class.blog.blog'))) {
            $navs = $isOwner
                            ? $parent->navs()->whereNull('ref_id')->get()
                            : $parent->navs(null, true)->whereNull('ref_id')->get();
        } else {
            $navs = $isOwner
                            ? $parent->navs()->get()
                            : $parent->navs(null, true)->get();
        }

        $result = [];
        foreach ($navs as $nav) {
            if ($transform)
                array_push($result, [
                    'value' => $nav->id,
                    'label' => $nav->findLang($code, 'name')
                ]);
            else
                array_push($result, [
                    'id'   => $nav->id,
                    'name' => $nav->findLang($code, 'name')
                ]);
        }

        return $result;
    }

    /**
     * @param Bool    $isOwner
     * @param Blog    $blog
     * @param Nav     $record
     * @param String  $code
     * @param Bool    $transform
     * @return Array
     */
    public function loadNavOptions(bool $isOwner, $blog, $record = null, $code = null, $transform = true): array
    {
        $result = [];
        $navs = $isOwner
                        ? $blog->navs()->get()
                        : $blog->navs(null, true)->get();

        foreach ($navs as $nav) {
            if (
                $record
                && (
                    $nav->id == $record->id
                    || $this->checkIsChildren($nav, $record->id)
                )
            )
                continue;

            if ($transform)
                array_push($result, [
                    'value' => $nav->id,
                    'label' => $this->loadNavPathText($nav, $code)
                ]);
            else
                array_push($result, [
                    'id'   => $nav->id,
                    'name' => $this->loadNavPathText($nav, $code)
                ]);
        }

        return $result;
    }

    /**
     * @param Nav  $object
     * @param Int  $id
     * @return Bool
     */
    private function checkIsChildren($object, int $id): bool
    {
        while (is_a($object, config('wk-core.class.morph-nav.nav'))) {
            $object = $object->parent();

            if ($object->ref_id == $id)
                return true;
        }

        return false;
    }
}
