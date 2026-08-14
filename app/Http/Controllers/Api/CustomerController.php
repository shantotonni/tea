<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query();

        if ($tier = $request->query('tier')) {
            $query->where('tier', $tier);
        }
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return response()->json(['data' => $query->orderByDesc('spent')->orderByDesc('id')->get()]);
    }

    public function show(Customer $customer)
    {
        $orders = $customer->orders()
            ->with('items')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($o) => [
                'code' => $o->code,
                'status' => $o->status,
                'total' => $o->total,
                'items_count' => $o->items_count,
                'created_at' => $o->created_at,
                'items' => $o->items->map(fn ($i) => [
                    'product_name' => $i->product_name, 'qty' => $i->qty, 'price' => $i->price,
                ]),
            ]);

        // real figures from the orders table (aggregate columns can drift)
        $realOrders = $customer->orders()->count();
        $realSpent = (int) $customer->orders()->where('status', '!=', 'Cancelled')->sum('total');

        return response()->json(['data' => [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'city' => $customer->city,
            'tier' => $customer->tier,
            'orders_count' => $customer->orders_count,
            'spent' => $customer->spent,
            'real_orders' => $realOrders,
            'real_spent' => $realSpent,
            'has_login' => ! empty($customer->password),
            'created_at' => $customer->created_at,
            'orders' => $orders,
        ]]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        $data['email'] = Str::lower($data['email']);

        return response()->json(['data' => Customer::create($data)], 201);
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $this->validated($request, $customer->id);
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        if (isset($data['email'])) {
            $data['email'] = Str::lower($data['email']);
        }
        $customer->update($data);

        return response()->json(['data' => $customer]);
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return response()->json(['message' => 'Customer deleted.']);
    }

    private function validated(Request $request, $ignoreId = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:150|unique:customers,email'.($ignoreId ? ",{$ignoreId}" : ''),
            'phone' => 'nullable|string|max:40',
            'city' => 'nullable|string|max:80',
            'tier' => 'nullable|in:Bronze,Silver,Gold',
            'password' => 'nullable|string|min:8',
        ]);
    }
}
