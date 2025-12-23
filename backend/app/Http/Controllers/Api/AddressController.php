<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    /**
     * 地址列表
     */
    public function index()
    {
        $user = auth()->user();
        $addresses = Address::where('user_id', $user->id)->get();

        return $this->success($addresses);
    }

    /**
     * 添加地址
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = auth()->user();

        try {
            // 如果设为默认地址，取消其他默认地址
            if ($request->is_default) {
                Address::where('user_id', $user->id)
                    ->update(['is_default' => 0]);
            }

            // 构建地址数据，只包含非空字段
            $addressData = [
                'user_id' => $user->id,
                'name' => $request->name,
                'phone' => $request->phone,
                'address' => $request->address,
                'is_default' => $request->is_default ? 1 : 0
            ];

            // 添加可选字段（如果存在）
            if ($request->has('email')) {
                $addressData['email'] = $request->email;
            }
            if ($request->has('city')) {
                $addressData['city'] = $request->city;
            }
            if ($request->has('postal_code')) {
                $addressData['postal_code'] = $request->postal_code;
            }

            $address = Address::create($addressData);

            return $this->success($address, 'Address added successfully', 201);
        } catch (\Exception $e) {
            \Log::error('Address create failed', ['error' => $e->getMessage()]);
            return $this->error('Failed to create address: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 更新地址
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:50',
            'email' => 'nullable|email|max:255',
            'phone' => 'sometimes|string|max:20',
            'address' => 'sometimes|string|max:255',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = auth()->user();
        $address = Address::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$address) {
            return $this->error('Address not found', 404);
        }

        try {
            // 如果设为默认地址，取消其他默认地址
            $isDefault = $request->input('is_default');
            if ($isDefault) {
                Address::where('user_id', $user->id)
                    ->where('id', '!=', $id)
                    ->update(['is_default' => 0]);
            }

            // 只更新存在的字段，并转换布尔值为整数
            $updateData = [];
            $fields = ['name', 'email', 'phone', 'address', 'city', 'postal_code'];
            foreach ($fields as $field) {
                if ($request->has($field)) {
                    $updateData[$field] = $request->input($field);
                }
            }
            // is_default 需要转换为整数
            if ($request->has('is_default')) {
                $updateData['is_default'] = $isDefault ? 1 : 0;
            }
            
            $address->fill($updateData);
            $address->save();

            return $this->success($address, 'Address updated successfully');
        } catch (\Exception $e) {
            \Log::error('Address update failed', ['error' => $e->getMessage(), 'address_id' => $id]);
            return $this->error('Failed to update address: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 删除地址
     */
    public function delete($id)
    {
        $user = auth()->user();
        $address = Address::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$address) {
            return $this->error('Address not found', 404);
        }

        $address->delete();

        return $this->success(null, 'Address deleted successfully');
    }

    /**
     * 设为默认地址
     */
    public function setDefault($id)
    {
        $user = auth()->user();
        $address = Address::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$address) {
            return $this->error('Address not found', 404);
        }

        // 取消其他默认地址
        Address::where('user_id', $user->id)
            ->update(['is_default' => 0]);

        $address->is_default = 1;
        $address->save();

        return $this->success($address, 'Default address set successfully');
    }
}


