<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $customers = Customer::query()
            ->latest()
            ->paginate(10);

        return CustomerResource::collection($customers);
    }

    public function store(
        StoreCustomerRequest $request
    ): CustomerResource {
        $customer = Customer::create(
            $request->validated()
        );

        return new CustomerResource($customer);
    }

    public function show(
        Customer $customer
    ): CustomerResource {
        return new CustomerResource($customer);
    }

    public function update(
        UpdateCustomerRequest $request,
        Customer $customer
    ): CustomerResource {
        $customer->update(
            $request->validated()
        );

        return new CustomerResource(
            $customer->fresh()
        );
    }

    public function destroy(
        Customer $customer
    ): JsonResponse {
        if ($customer->sales()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Customer tidak dapat dihapus karena sudah memiliki transaksi penjualan.',
            ], 422);
        }

        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customer berhasil dihapus.',
        ]);
    }
}
