<?php

namespace WalkerChiu\MorphNav\Models\Forms;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use WalkerChiu\Core\Models\Forms\FormRequest;

class NavFormRequest extends FormRequest
{
    /**
     * @Override Illuminate\Foundation\Http\FormRequest::getValidatorInstance
     */
    protected function getValidatorInstance()
    {
        $request = Request::instance();
        $data = $this->all();
        if (
            $request->isMethod('put')
            && empty($data['id'])
            && isset($request->id)
        ) {
            $data['id'] = (int) $request->id;
            $this->getInputSource()->replace($data);
        }

        return parent::getValidatorInstance();
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return Array
     */
    public function attributes()
    {
        return [
            'host_type'   => trans('php-morph-nav::system.host_type'),
            'host_id'     => trans('php-morph-nav::system.host_id'),
            'ref_id'      => trans('php-morph-nav::system.ref_id'),
            'type'        => trans('php-morph-nav::system.type'),
            'serial'      => trans('php-morph-nav::system.serial'),
            'identifier'  => trans('php-morph-nav::system.identifier'),
            'url'         => trans('php-morph-nav::system.url'),
            'target'      => trans('php-morph-nav::system.target'),
            'icon'        => trans('php-morph-nav::system.icon'),
            'order'       => trans('php-morph-nav::system.order'),
            'is_enabled'  => trans('php-morph-nav::system.is_enabled'),

            'name'        => trans('php-morph-nav::system.name'),
            'description' => trans('php-morph-nav::system.description')
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return Array
     */
    public function rules()
    {
        $rules = [
            'host_type'   => 'required_with:host_id|string',
            'host_id'     => 'required_with:host_type|integer|min:1',
            'ref_id'      => 'nullable|numeric|min:1',
            'type'        => '',
            'serial'      => '',
            'identifier'  => 'required|string|max:255',
            'url'         => '',
            'target'      => '',
            'icon'        => 'nullable|string|min:2',
            'order'       => 'nullable|numeric|min:0',
            'is_enabled'  => 'required|boolean',

            'name'        => 'required|string|max:255',
            'description' => ''
        ];

        $request = Request::instance();
        if (
            $request->isMethod('put')
            && isset($request->id)
        ) {
            $rules = array_merge($rules, ['id' => ['required','integer','min:1','exists:'.config('wk-core.table.morph-nav.navs').',id']]);
        }

        return $rules;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return Array
     */
    public function messages()
    {
        return [
            'id.required'             => trans('php-core::validation.required'),
            'id.integer'              => trans('php-core::validation.integer'),
            'id.min'                  => trans('php-core::validation.min'),
            'id.exists'               => trans('php-core::validation.exists'),
            'host_type.required_with' => trans('php-core::validation.required_with'),
            'host_type.string'        => trans('php-core::validation.string'),
            'host_id.required_with'   => trans('php-core::validation.required_with'),
            'host_id.integer'         => trans('php-core::validation.integer'),
            'host_id.min'             => trans('php-core::validation.min'),
            'ref_id.numeric'          => trans('php-core::validation.numeric'),
            'ref_id.min'              => trans('php-core::validation.min'),
            'identifier.required'     => trans('php-core::validation.required'),
            'identifier.max'          => trans('php-core::validation.max'),
            'icon.string'             => trans('php-core::validation.string'),
            'icon.min'                => trans('php-core::validation.min'),
            'order.numeric'           => trans('php-core::validation.numeric'),
            'order.min'               => trans('php-core::validation.min'),
            'is_enabled.required'     => trans('php-core::validation.required'),
            'is_enabled.boolean'      => trans('php-core::validation.boolean'),

            'name.required'           => trans('php-core::validation.required'),
            'name.string'             => trans('php-core::validation.string'),
            'name.max'                => trans('php-core::validation.max')
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after( function ($validator) {
            $data = $validator->getData();
            if (
                isset($data['host_type'])
                && isset($data['host_id'])
            ) {
                if (
                    config('wk-morph-nav.onoff.site-cms')
                    && !empty(config('wk-core.class.site-cms.site'))
                    && $data['host_type'] == config('wk-core.class.site-cms.site')
                ) {
                    $result = DB::table(config('wk-core.table.site-cms.sites'))
                                ->where('id', $data['host_id'])
                                ->exists();
                    if (!$result)
                        $validator->errors()->add('host_id', trans('php-core::validation.exists'));
                } elseif (
                    config('wk-morph-nav.onoff.site-mall')
                    && !empty(config('wk-core.class.site-mall.site'))
                    && $data['host_type'] == config('wk-core.class.site-mall.site')
                ) {
                    $result = DB::table(config('wk-core.table.site-mall.sites'))
                                ->where('id', $data['host_id'])
                                ->exists();
                    if (!$result)
                        $validator->errors()->add('host_id', trans('php-core::validation.exists'));
                } elseif (
                    config('wk-morph-nav.onoff.group')
                    && !empty(config('wk-core.class.group.group'))
                    && $data['host_type'] == config('wk-core.class.group.group')
                ) {
                    $result = DB::table(config('wk-core.table.group.groups'))
                                ->where('id', $data['host_id'])
                                ->exists();
                    if (!$result)
                        $validator->errors()->add('host_id', trans('php-core::validation.exists'));
                }
            }
            if (
                isset($data['identifier'])
                && !in_array($data['identifier'], ['#', '/'])
            ) {
                $result = config('wk-core.class.morph-nav.nav')::where('identifier', $data['identifier'])
                                ->when(isset($data['host_type']), function ($query) use ($data) {
                                    return $query->where('host_type', $data['host_type']);
                                  })
                                ->when(isset($data['host_id']), function ($query) use ($data) {
                                    return $query->where('host_id', $data['host_id']);
                                  })
                                ->when(isset($data['id']), function ($query) use ($data) {
                                    return $query->where('id', '<>', $data['id']);
                                  })
                                ->exists();
                if ($result)
                    $validator->errors()->add('identifier', trans('php-core::validation.unique', ['attribute' => trans('php-morph-nav::system.identifier')]));
            }
        });
    }
}
