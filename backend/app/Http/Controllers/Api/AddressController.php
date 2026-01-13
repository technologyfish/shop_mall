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
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'postcode' => 'required|string|max:20',
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

            // 构建地址数据
            $addressData = $request->only([
                'first_name', 'last_name', 'email', 'phone', 
                'address', 'city', 'postcode'
            ]);
            $addressData['user_id'] = $user->id;
            $addressData['is_default'] = $request->is_default ? 1 : 0;
            // 保持 name 字段兼容性（可选，建议合并）
            $addressData['name'] = $request->first_name . ' ' . $request->last_name;

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
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'sometimes|string|max:255',
            'city' => 'sometimes|string|max:100',
            'postcode' => 'sometimes|string|max:20',
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

            // 更新字段
            $updateData = $request->only([
                'first_name', 'last_name', 'email', 'phone', 
                'address', 'city', 'postcode'
            ]);
            
            if ($request->has('is_default')) {
                $updateData['is_default'] = $isDefault ? 1 : 0;
            }

            if ($request->has('first_name') || $request->has('last_name')) {
                $f = $request->input('first_name', $address->first_name);
                $l = $request->input('last_name', $address->last_name);
                $updateData['name'] = trim($f . ' ' . $l);
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


