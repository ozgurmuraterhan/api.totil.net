<?php


namespace PickBazar\Database\Repositories;

use Ignited\LaravelOmnipay\Facades\OmnipayFacade as Omnipay;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PickBazar\Database\Models\Coupon;
use PickBazar\Database\Models\Order;
use PickBazar\Events\OrderCreated;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;
use Prettus\Validator\Exceptions\ValidatorException;

class OrderRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'tracking_number' => 'like',
    ];
    /**
     * @var string[]
     */
    protected $dataArray = [
        'tracking_number',
        'customer_id',
        'status',
        'amount',
        'sales_tax',
        'paid_total',
        'total',
        'delivery_time',
        'payment_gateway',
        'discount',
        'coupon_id',
        'payment_id',
        'logistics_provider',
        'billing_address',
        'shipping_address',
        'delivery_fee',
        'customer_contact'
    ];

    public function boot()
    {
        try {
            $this->pushCriteria(app(RequestCriteria::class));
        } catch (RepositoryException $e) {
        }
    }

    /**
     * Configure the Model
     **/
    public function model()
    {
        return Order::class;
    }

    /**
     * @param $request
     * @return LengthAwarePaginator|JsonResponse|Collection|mixed
     */
    public function storeOrder($request)
    {
        $request['tracking_number'] = Str::random(12);
        $request['customer_id'] = $request->user()->id;
        $discount = $this->calculateDiscount($request);
        if ($discount) {
            $request['paid_total'] = $request['amount'] + $request['sales_tax'] + $request['delivery_fee'] - $discount;
            $request['total'] = $request['amount'] + $request['sales_tax'] + $request['delivery_fee'] - $discount;
            $request['discount'] =  $discount;
        } else {
            $request['paid_total'] = $request['amount'] + $request['sales_tax'] + $request['delivery_fee'];
            $request['total'] = $request['amount'] + $request['sales_tax'] + $request['delivery_fee'];
        }
        $payment_gateway = $request['payment_gateway'];
        switch ($payment_gateway) {
            case 'cod':
                // Cash on Delivery no need to capture payment
                return $this->createOrder($request);
                break;
        }

        $response = $this->capturePayment($request);
        if ($response->isSuccessful()) {
            $payment_id = $response->getTransactionReference();
            $request['payment_id'] = $payment_id;
            return $this->createOrder($request);
        } elseif ($response->isRedirect()) {
            return $response->getRedirectResponse();
        } else {
            return ['message' => 'Payment not Successful!', 'code' => 404, 'success' => false];
        }
    }

    /**
     * @param $request
     * @return mixed
     */
    protected function capturePayment($request)
    {
        $card = Omnipay::creditCard($request['card']);
        $amount = $request['paid_total'];
        $currency = 'USD';
        $transaction =
            Omnipay::purchase(array(
                'amount'   => $amount,
                'currency' => $currency,
                'card'     => $card,
            ));
        return $transaction->send();
    }

    /**
     * @param $request
     * @return array|LengthAwarePaginator|Collection|mixed
     */
    protected function createOrder($request)
    {
        try {
            $orderInput = $request->only($this->dataArray);
            $products = $this->processProducts($request['products']);
            $order = $this->create($orderInput);
            $order->products()->attach($products);
            event(new OrderCreated($order));
            return $order;
        } catch (ValidatorException $e) {
            return ['message' => 'Something went wrong!', 'code' => 500, 'error' => true];
        }
    }

    protected function processProducts($products)
    {
        foreach ($products as $key => $product) {
            if (!isset($product['variation_option_id'])) {
                $product['variation_option_id'] = null;
                $products[$key] = $product;
            }
        }
        return $products;
    }

    protected function calculateDiscount($request)
    {
        try {
            if (!isset($request['coupon_id'])) {
                return false;
            }
            $coupon = Coupon::findOrFail($request['coupon_id']);
            if (!$coupon->is_valid) {
                return false;
            }
            switch ($coupon->type) {
                case 'percentage':
                    return ($request['amount'] * $coupon->amount) / 100;
                case 'fixed':
                    return $coupon->amount;
                    break;
                case 'free_shipping':
                    return isset($request['delivery_fee']) ? $request['delivery_fee'] : false;
                    break;
            }
            return false;
        } catch (\Exception $exception) {
            return false;
        }
    }
}
