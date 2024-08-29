<?php

namespace Webkul\RestApi\Http\Controllers\V1\Admin\Settings;

use Illuminate\Http\Request;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Core\Rules\Code;
use Webkul\RestApi\Http\Resources\V1\Admin\Settings\LocaleResource;

class LocaleController extends SettingController
{
    /**
     * Repository class name.
     */
    public function repository(): string
    {
        return LocaleRepository::class;
    }

    /**
     * Resource class name.
     */
    public function resource(): string
    {
        return LocaleResource::class;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'code'      => ['required', 'unique:locales,code', new Code],
            'direction'   => 'required|in:ltr,rtl',
            'logo_path'   => 'array',
            'logo_path.*' => 'image|extensions:jpeg,jpg,png,svg,webp',
        ]);

        $locale = $this->getRepositoryInstance()->create(request()->only([
            'code',
            'name',
            'direction',
            'logo_path',
        ]));

        return response([
            'data'    => new LocaleResource($locale),
            'message' => trans('rest-api::app.admin.settings.locales.create-success'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, int $id)
    {
        $request->validate([
            'code'        => ['required', 'unique:locales,code,'.$id, new Code],
            'name'        => 'required',
            'direction'   => 'required|in:ltr,rtl',
            'logo_path'   => 'array',
            'logo_path.*' => 'image|extensions:jpeg,jpg,png,svg,webp',
        ]);

        $locale = $this->getRepositoryInstance()->update(request()->only([
            'code',
            'name',
            'direction',
            'logo_path',
        ]), $request->id);

        return response([
            'data'    => new LocaleResource($locale),
            'message' => trans('rest-api::app.admin.settings.locales.update-success'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(int $id)
    {
        $this->getRepositoryInstance()->findOrFail($id);

        if ($this->getRepositoryInstance()->count() == 1) {
            return response([
                'message' => trans('rest-api::app.admin.settings.locales.error.last-item-delete'),
            ]);
        }

        $this->getRepositoryInstance()->delete($id);

        return response([
            'message' => trans('rest-api::app.admin.settings.locales.delete-success'),
        ]);
    }
}
