<?php

namespace PickBazar\Http\Controllers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PickBazar\Database\Models\Address;
use PickBazar\Database\Repositories\AddressRepository;
use PickBazar\Http\Requests\AddressRequest;
use Prettus\Validator\Exceptions\ValidatorException;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use PickBazar\Database\Models\Order;
use PickBazar\Database\Models\Product;
use PickBazar\Enums\Permission;
use Spatie\Permission\Models\Permission as ModelsPermission;

class AnalyticsController extends CoreController
{
    public $repository;

    public function __construct(AddressRepository $repository)
    {
        $this->repository = $repository;
    }


    public function analytics(Request $request)
    {
        if ($request->user() && $request->user()->hasPermissionTo(Permission::SUPER_ADMIN)) {
            $totalRevenue = DB::table('orders')->whereDate('created_at', '>', Carbon::now()->subDays(30))->sum('paid_total');
            $todaysRevenue = DB::table('orders')->whereDate('created_at', '>', Carbon::now()->subDays(1))->sum('paid_total');
            $totalOrders = DB::table('orders')->whereDate('created_at', '>', Carbon::now()->subDays(30))->count();
            $customerPermission = ModelsPermission::where('name', Permission::CUSTOMER)->first();
            $newCustomers = $customerPermission->users()->whereDate('created_at', '>', Carbon::now()->subDays(30))->count();
            $totalYearSaleByMonth =
                $orders = DB::table('orders')->selectRaw(
                    "sum(paid_total) as total, DATE_FORMAT(created_at,'%M') as month"
                )->whereYear('created_at', date('Y'))->groupBy('month')->get();

            $months = [
                "January",
                "February",
                "March",
                "April",
                "May",
                "June",
                "July",
                "August",
                "September",
                "October",
                "November",
                "December",
            ];

            $processedData = [];

            foreach ($months as $key => $month) {
                foreach ($totalYearSaleByMonth as $value) {
                    if ($value->month === $month) {
                        $processedData[$key] = $value;
                    } else {
                        $processedData[$key] = ['total' => 0, 'month' => $month];
                    }
                }
            }
            return [
                'totalRevenue' => $totalRevenue,
                'todaysRevenue' => $todaysRevenue,
                'totalOrders' => $totalOrders,
                'newCustomers' =>  $newCustomers,
                'totalYearSaleByMonth' => $processedData
            ];
        }
        throw ValidationException::withMessages([
            'error' => ['User is not logged in or doesn\'t have enough permission.'],
        ]);
    }

    public function popularProducts(Request $request)
    {
        $limit = $request->limit ? $request->limit : 10;
        $products = Product::withCount('orders')->orderBy('orders_count', 'desc')->limit($limit)->get();
        return $products;
    }
}
