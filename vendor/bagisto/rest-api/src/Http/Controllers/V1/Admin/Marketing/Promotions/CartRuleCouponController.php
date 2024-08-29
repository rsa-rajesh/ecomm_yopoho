<?php

namespace Webkul\RestApi\Http\Controllers\V1\Admin\Marketing\Promotions;

use Illuminate\Http\Request;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\CartRule\Repositories\CartRuleCouponRepository;
use Webkul\RestApi\Http\Controllers\V1\Admin\Marketing\MarketingController;
use Webkul\RestApi\Http\Resources\V1\Admin\Marketing\Promotions\CartRuleCouponResource;

class CartRuleCouponController extends MarketingController
{
    /**
     * Repository class name.
     */
    public function repository(): string
    {
        return CartRuleCouponRepository::class;
    }

    /**
     * Resource class name.
     */
    public function resource(): string
    {
        return CartRuleCouponResource::class;
    }

    /**
     * Get all cart rule coupons.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(int $cartRuleId)
    {
        $coupons = $this->getRepositoryInstance()->where('cart_rule_id', $cartRuleId)->get();

        return response([
            'data' => $this->getResourceCollection($coupons),
        ]);
    }

    /**
     * Generate coupon code for cart rule.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, int $cartRuleId)
    {
        $request->validate([
            'coupon_qty'  => 'required|integer|min:1',
            'code_length' => 'required|integer|min:10',
            'code_format' => 'required',
        ]);

        $this->getRepositoryInstance()->generateCoupons($request->only(
            'coupon_qty',
            'code_length',
            'code_format',
            'code_prefix',
            'code_suffix'
        ), $cartRuleId);

        return response([
            'message' => trans('rest-api::app.admin.marketing.promotions.cart-rule-coupons.create-success'),
        ]);
    }

    /**
     * Show specific cart rule coupon.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(int $cartRuleId, int $id)
    {
        $coupon = $this->getRepositoryInstance()
            ->where('cart_rule_id', $cartRuleId)
            ->where('id', $id)
            ->firstOrFail();

        return response([
            'data' => new CartRuleCouponResource($coupon),
        ]);
    }

    /**
     * Delete specific cart rule coupon.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(int $cartRuleId, int $id)
    {
        $this->getRepositoryInstance()
            ->where('cart_rule_id', $cartRuleId)
            ->where('id', $id)
            ->firstOrFail();

        $this->getRepositoryInstance()->delete($id);

        return response([
            'message' => trans('rest-api::app.admin.marketing.promotions.cart-rule-coupons.delete-success'),
        ]);
    }

    /**
     * Mass delete the coupons.
     *
     * @return \Illuminate\Http\Response
     */
    public function massDestroy(MassDestroyRequest $massDestroyRequest, int $cartRuleId)
    {
        foreach ($massDestroyRequest->indices as $couponId) {
            $this->getRepositoryInstance()
                ->where('cart_rule_id', $cartRuleId)
                ->where('id', $couponId)
                ->firstOrFail();

            $this->getRepositoryInstance()->delete($couponId);
        }

        return response([
            'message' => trans('rest-api::app.admin.marketing.promotions.cart-rule-coupons.mass-operations.delete-success'),
        ]);
    }
}
