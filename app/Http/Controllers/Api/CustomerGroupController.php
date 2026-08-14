<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerGroup;
use Illuminate\Http\Request;

class CustomerGroupController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => CustomerGroup::withCount('customers')->orderBy('name')->get(),
        ]);
    }

    public function show(CustomerGroup $group)
    {
        $group->load('customers:id,name,email,tier');

        return response()->json(['data' => [
            'id' => $group->id,
            'name' => $group->name,
            'description' => $group->description,
            'members' => $group->customers,
            'member_ids' => $group->customers->pluck('id'),
        ]]);
    }

    public function store(Request $request)
    {
        return response()->json(['data' => CustomerGroup::create($this->validated($request))], 201);
    }

    public function update(Request $request, CustomerGroup $group)
    {
        $group->update($this->validated($request, $group->id));

        return response()->json(['data' => $group]);
    }

    public function destroy(CustomerGroup $group)
    {
        $group->delete(); // members pivot cascades; promo customer_group_id nulls out

        return response()->json(['message' => 'Group deleted.']);
    }

    /** PUT /api/customer-groups/{group}/members — replace the whole member list */
    public function syncMembers(Request $request, CustomerGroup $group)
    {
        $data = $request->validate([
            'customer_ids' => 'array',
            'customer_ids.*' => 'integer|exists:customers,id',
        ]);

        $group->customers()->sync($data['customer_ids'] ?? []);

        return response()->json(['message' => 'Members updated.', 'count' => $group->customers()->count()]);
    }

    private function validated(Request $request, $ignoreId = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:80|unique:customer_groups,name'.($ignoreId ? ",{$ignoreId}" : ''),
            'description' => 'nullable|string|max:200',
        ]);
    }
}
