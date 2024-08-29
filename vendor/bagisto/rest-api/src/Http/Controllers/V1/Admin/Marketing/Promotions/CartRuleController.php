<?php

namespace Webkul\RestApi\Http\Controllers\V1\Admin\Marketing\Promotions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Webkul\CartRule\Repositories\CartRuleRepository;
use Webkul\RestApi\Http\Controllers\V1\Admin\Marketing\MarketingController;
use Webkul\RestApi\Http\Resources\V1\Admin\Marketing\Promotions\CartRuleResource;

class CartRuleController extends MarketingController
{
    /**
     * Repository class name.
     */
    public function repository(): string
    {
        return CartRuleRepository::class;
    }

    /**
     * Resource class name.
     */
    public function resource(): string
    {
        return CartRuleResource::class;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'                => 'required',
            'channels'            => 'required|array|min:1',
            'customer_groups'     => 'required|array|min:1',
            'coupon_type'         => 'required',
            'use_auto_generation' => 'required_if:coupon_type,==,1',
            'coupon_code'         => 'required_if:use_auto_generation,==,0|unique:cart_rule_coupons,code',
            'starts_from'         => 'nullable|date',
            'ends_till'           => 'nullable|date|after_or_equal:starts_from',
            'action_type'         => 'required',
            'discount_amount'     => 'required|numeric',
        ]);

        Event::dispatch('promotions.cart_rule.create.before');

        $cartRule = $this->getRepositoryInstance()->create($request->all());

        Event::dispatch('promotions.cart_rule.create.after', $cartRule);

        return response([
            'data'    => new CartRuleResource($cartRule),
            'message' => trans('rest-api::app.admin.marketing.promotions.cart-rules.create-success'),
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
            'name'                => 'required',
            'channels'            => 'required|array|min:1',
            'customer_groups'     => 'required|array|min:1',
            'coupon_type'         => 'required',
            'use_auto_generation' => 'required_if:coupon_type,==,1',
            'starts_from'         => 'nullable|date',
            'ends_till'           => 'nullable|date|after_or_equal:starts_from',
            'action_type'         => 'required',
            'discount_amount'     => 'required|numeric',
        ]);

        $cartRule = $this->getRepositoryInstance()->findOrFail($id);

        if ($cartRule->coupon_type) {
            if ($cartRule->cart_rule_coupon) {
                $request->validate([
                    'coupon_code' => 'required_if:use_auto_generation,==,0|unique:cart_rule_coupons,code,'.$cartRule->cart_rule_coupon->id,
                ]);
            } else {
                $request->validate([
                    'coupon_code' => 'required_if:use_auto_generation,==,0|unique:cart_rule_coupons,code',
                ]);
            }
        }

        Event::dispatch('promotions.cart_rule.update.before', $id);

        $cartRule = $this->getRepositoryInstance()->update($request->all(), $id);

        Event::dispatch('promotions.cart_rule.update.after', $cartRule);

        return response([
            'data'    => new CartRuleResource($cartRule),
            'message' => trans('rest-api::app.admin.marketing.promotions.cart-rules.update-success'),
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

        Event::dispatch('promotions.cart_rule.delete.before', $id);

        $this->getRepositoryInstance()->delete($id);

        Event::dispatch('promotions.cart_rule.delete.after', $id);

        return response([
            'message' => trans('rest-api::app.admin.marketing.promotions.cart-rules.delete-success'),
        ]);
    }
}
