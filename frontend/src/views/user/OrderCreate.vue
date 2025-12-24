<template>
  <div class="order-create-page">
    <div class="container">
      <h1 class="page-title">Checkout</h1>

      <div class="checkout-grid">
        <!-- Left Column: Address & Items -->
        <div class="main-col">
          <!-- Address Section -->
          <div class="section-card address-section">
            <div class="section-header">
              <h2>Shipping Address</h2>
              <el-button link type="primary" @click="showAddressDialog">
                Select / Manage Address
              </el-button>
            </div>

            <div v-if="selectedAddress" class="address-display">
              <div class="addr-info">
                <div class="name-row">
                  <span class="name">{{ selectedAddress.name }}</span>
                  <span class="phone">{{ selectedAddress.phone }}</span>
                </div>
                <div class="email" v-if="selectedAddress.email">{{ selectedAddress.email }}</div>
                <div class="detail">{{ selectedAddress.address }}</div>
                <div class="city-postal" v-if="selectedAddress.city || selectedAddress.postal_code">
                  <span v-if="selectedAddress.city">{{ selectedAddress.city }}</span>
                  <span v-if="selectedAddress.postal_code">{{ selectedAddress.postal_code }}</span>
                </div>
              </div>
              <el-tag v-if="selectedAddress.is_default" size="small" type="success" class="mt-2">Default</el-tag>
            </div>
            <el-empty v-else description="No address selected. Please add or select one." />
          </div>

          <!-- Order Items -->
          <div class="section-card items-section">
            <h2>Order Items</h2>
            <div class="order-items">
              <div v-for="item in cartItems" :key="item.id" class="item-row">
                <img :src="item.product?.image || item.product?.main_image || '/placeholder-product.jpg'" class="item-img" />
                <div class="item-info">
                  <h3>{{ item.product?.name }}</h3>
                  <p class="sku">Qty: {{ item.quantity }}</p>
                </div>
                <div class="item-price">
                  ¥{{ (item.product?.price * item.quantity).toFixed(2) }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column: Summary -->
        <div class="sidebar-col">
          <div class="summary-card">
            <h2>Order Summary</h2>
            <div class="summary-row">
              <span>Subtotal</span>
              <span>£{{ subtotal.toFixed(2) }}</span>
            </div>
            <div class="summary-row" v-if="shippingFee > 0">
              <span>Shipping</span>
              <span>£{{ shippingFee.toFixed(2) }}</span>
            </div>
            <div class="summary-row" v-else-if="shippingSettings && shippingSettings.shipping_fee > 0">
              <span>Shipping</span>
              <span style="color: #67c23a;">Free</span>
            </div>
            <div class="summary-row discount" v-if="hasFirstOrderDiscount && discountAmount > 0">
              <span>First Order Discount ({{ discountPercent }}%)</span>
              <span style="color: #67c23a;">-£{{ discountAmount.toFixed(2) }}</span>
            </div>
            <div class="divider"></div>
            <div class="summary-row total">
              <span>Total</span>
              <span>£{{ totalPrice.toFixed(2) }}</span>
            </div>

            <el-button 
              type="primary" 
              class="btn-submit" 
              size="large" 
              :loading="submitting"
              @click="handleSubmitOrder"
              :disabled="!selectedAddressId || cartItems.length === 0"
            >
              Place Order
            </el-button>
          </div>
        </div>
      </div>
    </div>

    <!-- Address Selection Dialog -->
    <el-dialog 
      v-model="addressDialogVisible" 
      title="常用地址" 
      width="480px"
      :close-on-click-modal="false"
      class="address-select-dialog"
      align-center
    >
      <div class="address-dialog-content">
        <!-- 有地址时显示列表 -->
        <template v-if="addresses.length > 0">
          <div class="address-list">
            <div 
              v-for="addr in addresses" 
              :key="addr.id" 
              class="address-item"
              :class="{ 'selected': selectedAddressId === addr.id }"
              @click="selectAddress(addr.id)"
            >
              <div class="address-info">
                <div class="name-row">
                  <span class="name">{{ addr.name }}</span>
                  <span class="phone">{{ addr.phone }}</span>
                  <el-tag v-if="addr.is_default" size="small" type="success" style="margin-left: 10px;">Default</el-tag>
                </div>
                <div class="email" v-if="addr.email">{{ addr.email }}</div>
                <div class="address-text">{{ addr.address }}</div>
                <div class="city-postal" v-if="addr.city || addr.postal_code">
                  <span v-if="addr.city">{{ addr.city }}</span>
                  <span v-if="addr.postal_code" style="margin-left: 10px;">{{ addr.postal_code }}</span>
                </div>
              </div>
              <div class="address-actions">
                <el-button link type="primary" size="small" @click.stop="editAddress(addr)">
                  编辑
                </el-button>
                <el-button link type="danger" size="small" @click.stop="deleteAddress(addr.id)">
                  删除
                </el-button>
              </div>
            </div>
          </div>
          
          <el-button 
            type="primary" 
            class="add-address-btn" 
            @click="showAddAddressForm"
            :icon="Plus"
          >
            添加新地址
          </el-button>
        </template>
        
        <!-- 无地址时显示空状态 -->
        <template v-else>
          <div class="empty-address">
            <div class="empty-icon">
              <svg viewBox="0 0 100 100" width="80" height="80" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="20" y="25" width="60" height="50" rx="4" stroke="#dcdfe6" stroke-width="2"/>
                <path d="M20 35 L50 55 L80 35" stroke="#dcdfe6" stroke-width="2"/>
                <circle cx="50" cy="70" r="3" fill="#dcdfe6"/>
              </svg>
            </div>
            <p class="empty-text">暂无收货地址</p>
            <el-button 
              type="primary" 
              @click="showAddAddressForm"
              :icon="Plus"
            >
              添加新地址
            </el-button>
          </div>
        </template>
      </div>
      
      <template #footer>
        <el-button @click="addressDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="confirmAddress" :disabled="!selectedAddressId">
          确定
        </el-button>
      </template>
    </el-dialog>

    <!-- Add/Edit Address Dialog -->
    <el-dialog 
      v-model="addressFormDialogVisible" 
      :title="editingAddress ? '编辑地址' : '添加地址'"
      width="500px"
      :close-on-click-modal="false"
    >
      <el-form 
        ref="addressFormRef" 
        :model="addressForm" 
        :rules="addressRules"
        label-width="120px"
      >
        <el-form-item label="Full Name" prop="name">
          <el-input v-model="addressForm.name" placeholder="Full Name" />
        </el-form-item>
        
        <el-form-item label="Email" prop="email">
          <el-input v-model="addressForm.email" placeholder="Email" />
        </el-form-item>
        
        <el-form-item label="Phone" prop="phone">
          <el-input v-model="addressForm.phone" placeholder="Phone" />
        </el-form-item>
        
        <el-form-item label="Shipping Address" prop="address">
          <el-input 
            v-model="addressForm.address" 
            type="textarea" 
            :rows="3"
            placeholder="Shipping Address"
          />
        </el-form-item>
        
        <el-form-item label="City" prop="city">
          <el-input v-model="addressForm.city" placeholder="City" />
        </el-form-item>
        
        <el-form-item label="Postal Code" prop="postal_code">
          <el-input v-model="addressForm.postal_code" placeholder="Postal Code" />
        </el-form-item>
        
        <el-form-item label="设为默认">
          <el-switch v-model="addressForm.is_default" />
        </el-form-item>
      </el-form>
      
      <template #footer>
        <el-button @click="addressFormDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="saveAddress" :loading="saving">
          保存
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import { useCartStore } from '@/store/cart'
import { useUserStore } from '@/store/user'
import { getAddresses, addAddress, updateAddress, deleteAddress as deleteAddressApi } from '@/api/address'
import { createOrder } from '@/api/order'
import { getProduct } from '@/api/product'
import { getShippingSettings } from '@/api/shipping'
import axios from 'axios'

const router = useRouter()
const route = useRoute()
const cartStore = useCartStore()
const userStore = useUserStore()

const addresses = ref([])
const selectedAddressId = ref(null)
const submitting = ref(false)
const addressDialogVisible = ref(false)
const addressFormDialogVisible = ref(false)
const shippingSettings = ref(null)
const editingAddress = ref(null)
const saving = ref(false)

// 首单优惠
const hasFirstOrderDiscount = ref(false)
const discountPercent = ref(0)

// Order Data - 区分Buy Now和购物车结算
const orderItems = ref([]) // 订单商品列表
const isBuyNow = ref(false) // 是否是立即购买

// Cart Data - 只包含选中的商品
const cartItems = computed(() => {
  if (isBuyNow.value) {
    return orderItems.value
  }
  
  // 从localStorage获取选中的商品ID
  const checkoutCartIds = JSON.parse(localStorage.getItem('checkout_cart_ids') || '[]')
  
  // 如果有选中的商品ID，只返回这些商品
  if (checkoutCartIds.length > 0) {
    return cartStore.cartItems.filter(item => checkoutCartIds.includes(item.id))
  }
  
  // 否则返回所有购物车商品
  return cartStore.cartItems
})

// 小计
const subtotal = computed(() => {
  return cartItems.value.reduce((sum, item) => {
    return sum + (item.product?.price || 0) * item.quantity
  }, 0)
})

// 运费
const shippingFee = computed(() => {
  if (!shippingSettings.value || subtotal.value === 0) return 0
  
  const settings = shippingSettings.value
  if (!settings.shipping_fee || parseFloat(settings.shipping_fee) === 0) {
    return 0
  }
  
  // 如果有免运费门槛且达到门槛
  if (settings.free_shipping_threshold && 
      parseFloat(settings.free_shipping_threshold) > 0 && 
      subtotal.value >= parseFloat(settings.free_shipping_threshold)) {
    return 0
  }
  
  return parseFloat(settings.shipping_fee)
})

// 折扣金额
const discountAmount = computed(() => {
  if (!hasFirstOrderDiscount.value || discountPercent.value <= 0) return 0
  return subtotal.value * (discountPercent.value / 100)
})

// 总价（含运费，扣除折扣）
const totalPrice = computed(() => {
  return subtotal.value + shippingFee.value - discountAmount.value
})

// Address Form
const addressFormRef = ref(null)
const addressForm = reactive({
  name: '',
  email: '',
  phone: '',
  address: '',
  city: '',
  postal_code: '',
  is_default: false
})
const addressRules = {
  name: [{ required: true, message: 'Full Name is required', trigger: 'blur' }],
  email: [
    { required: true, message: 'Email is required', trigger: 'blur' },
    { type: 'email', message: 'Invalid email format', trigger: 'blur' }
  ],
  phone: [{ required: true, message: 'Phone is required', trigger: 'blur' }],
  address: [{ required: true, message: 'Shipping Address is required', trigger: 'blur' }],
  city: [{ required: true, message: 'City is required', trigger: 'blur' }],
  postal_code: [{ required: true, message: 'Postal Code is required', trigger: 'blur' }]
}

onMounted(async () => {
  // 获取运费设置
  try {
    const res = await getShippingSettings()
    shippingSettings.value = res.data.data
  } catch (error) {
    console.error('Failed to load shipping settings:', error)
  }
  
  // 检查首单优惠
  try {
    // 先获取用户信息
    if (userStore.isLoggedIn) {
      await userStore.fetchUserInfo()
      
      // 检查用户是否有首单优惠资格
      if (userStore.userInfo?.first_order_discount === 1) {
        // 获取首单优惠促销活动信息
        const promoRes = await axios.get('/api/off-code/promotion')
        if (promoRes.data.data?.active) {
          hasFirstOrderDiscount.value = true
          discountPercent.value = promoRes.data.data.discount_value
        }
      }
    }
  } catch (error) {
    console.error('Failed to check first order discount:', error)
  }
  
  // 检查是否是Buy Now模式
  if (route.query.type === 'buynow' && route.query.productId) {
    isBuyNow.value = true
    // 获取商品信息
    try {
      const productId = parseInt(route.query.productId)
      const quantity = parseInt(route.query.quantity) || 1
      const res = await getProduct(productId)
      const product = res.data.data
      
      // 构造订单商品列表
      orderItems.value = [{
        id: productId,
        product_id: productId,
        product: product,
        quantity: quantity
      }]
    } catch (error) {
      ElMessage.error('Failed to load product')
      router.push('/shop')
      return
    }
  } else {
    // 购物车结算模式
    isBuyNow.value = false
    await cartStore.fetchCart()
    
    // 检查是否有选中的商品
    const checkoutCartIds = JSON.parse(localStorage.getItem('checkout_cart_ids') || '[]')
    if (checkoutCartIds.length === 0 && cartStore.cartItems.length === 0) {
      ElMessage.warning('Cart is empty')
      router.push('/cart')
      return
    }
  }
  
  await fetchAddresses()
})

const fetchAddresses = async () => {
  try {
    const res = await getAddresses()
    addresses.value = res.data.data || []
    // Auto select default or first
    const defaultAddr = addresses.value.find(a => a.is_default)
    if (defaultAddr) {
      selectedAddressId.value = defaultAddr.id
    } else if (addresses.value.length > 0) {
      selectedAddressId.value = addresses.value[0].id
    }
  } catch (error) {
    console.error(error)
  }
}

const selectedAddress = computed(() => {
  return addresses.value.find(a => a.id === selectedAddressId.value)
})

// 显示地址选择弹窗
const showAddressDialog = () => {
  addressDialogVisible.value = true
}

// 选择地址
const selectAddress = (addressId) => {
  selectedAddressId.value = addressId
}

// 确认选择地址
const confirmAddress = () => {
  if (selectedAddressId.value) {
    addressDialogVisible.value = false
    ElMessage.success('Address selected')
  }
}

// 显示添加地址表单
const showAddAddressForm = () => {
  editingAddress.value = null
  Object.assign(addressForm, {
    name: '',
    email: '',
    phone: '',
    address: '',
    city: '',
    postal_code: '',
    is_default: false
  })
  addressFormDialogVisible.value = true
}

// 编辑地址
const editAddress = (addr) => {
  editingAddress.value = addr
  Object.assign(addressForm, {
    name: addr.name || '',
    email: addr.email || '',
    phone: addr.phone || '',
    address: addr.address || '',
    city: addr.city || '',
    postal_code: addr.postal_code || '',
    is_default: addr.is_default || false
  })
  addressFormDialogVisible.value = true
}

// 保存地址
const saveAddress = async () => {
  if (!addressFormRef.value) return
  
  await addressFormRef.value.validate(async (valid) => {
    if (!valid) return
    
    saving.value = true
    try {
      if (editingAddress.value) {
        // 更新地址
        await updateAddress(editingAddress.value.id, addressForm)
        ElMessage.success('Address updated')
      } else {
        // 添加地址
        await addAddress(addressForm)
        ElMessage.success('Address added')
      }
      
      addressFormDialogVisible.value = false
      await fetchAddresses()
      
      // 如果设为默认，自动选中
      if (addressForm.is_default) {
        const defaultAddr = addresses.value.find(a => a.is_default)
        if (defaultAddr) {
          selectedAddressId.value = defaultAddr.id
        }
      }
    } catch (error) {
      ElMessage.error(error.response?.data?.message || 'Failed to save address')
    } finally {
      saving.value = false
    }
  })
}

// 删除地址
const deleteAddress = async (addressId) => {
  try {
    await ElMessageBox.confirm('确定要删除这个地址吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
    
    await deleteAddressApi(addressId)
    ElMessage.success('Address deleted')
    await fetchAddresses()
    
    // 如果删除的是当前选中的地址，清空选中
    if (selectedAddressId.value === addressId) {
      selectedAddressId.value = null
    }
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('Failed to delete address')
    }
  }
}

const handleSubmitOrder = async () => {
  if (!selectedAddressId.value) {
    ElMessage.warning('Please select an address')
    return
  }

  submitting.value = true
  try {
    const selectedAddress = addresses.value.find(a => a.id === selectedAddressId.value)
    if (!selectedAddress) throw new Error('Address not found')

    const orderItemsData = cartItems.value.map(item => ({
      product_id: item.product_id || item.product?.id || item.id,
      quantity: item.quantity
    }))

    const res = await createOrder({
      items: orderItemsData,
      ship_name: selectedAddress.name,
      ship_email: selectedAddress.email,
      ship_phone: selectedAddress.phone,
      ship_address: selectedAddress.address,
      ship_city: selectedAddress.city,
      ship_postal_code: selectedAddress.postal_code,
      remark: '',
      clear_cart: !isBuyNow.value // Buy Now模式不清空购物车，购物车结算才清空
    })
    
    const order = res.data.data
    
    // ElMessage.success('Order created successfully! Redirecting to payment...')
    
    // 如果是购物车结算，刷新购物车（应该已清空）
    if (!isBuyNow.value) {
      await cartStore.fetchCart()
    }
    
    // 跳转到支付页面
    setTimeout(() => {
      router.push(`/payment/${order.id}`)
    }, 500)
  } catch (error) {
    console.error(error)
    ElMessage.error(error.response?.data?.message || error.message || 'Failed to place order')
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped lang="scss">
.order-create-page {
  min-height: 100vh;
  padding-top: 40px;
  background-color: var(--primary-lighter);
  padding-bottom: 60px;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

@media (max-width: 768px) {
  .container {
    max-width: 100%;
  }
}

.page-title {
  font-size: 28px;
  color: var(--text-white);
  margin-bottom: 30px;
  font-family: 'Courier New', serif;
}

.checkout-grid {
  display: grid;
  grid-template-columns: 1fr 350px;
  gap: 30px;

  @media (max-width: 768px) {
    grid-template-columns: 1fr;
  }
}

.section-card {
  background: var(--text-white);
  border-radius: 8px;
  padding: 25px;
  margin-bottom: 20px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);

  h2 {
    font-size: 18px;
    margin-bottom: 20px;
    border-bottom: 1px solid var(--primary-lighter);
    padding-bottom: 10px;
  }
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  border-bottom: 1px solid var(--primary-lighter);
  padding-bottom: 10px;

  h2 {
    margin: 0;
    border: none;
    padding: 0;
  }
}

.address-display {
  padding: 15px;
  border: 1px solid #eee;
  border-radius: 8px;

  .name-row {
    margin-bottom: 8px;
    
    .name {
      color: #000;
      font-weight: bold;
      font-size: 16px;
      margin-right: 15px;
    }
    
    .phone {
      color:  #000;
      font-size: 14px;
    }
  }
  
  .email {
    color:  #000;
    font-size: 14px;
    margin-bottom: 8px;
  }
  
  .detail {
    color:  #000;
    line-height: 1.4;
    margin-bottom: 5px;
  }
  
  .city-postal {
    color: var(--text-white);
    font-size: 14px;
    margin-top: 5px;
    
    span {
      margin-right: 10px;
    }
  }
  
  .mt-2 {
    margin-top: 8px;
  }
}

.order-items {
  .item-row {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px 0;
    border-bottom: 1px solid #f5f5f5;

    &:last-child {
      border-bottom: none;
    }

    .item-img {
      width: 60px;
      height: 60px;
      object-fit: cover;
      border-radius: 4px;
    }

    .item-info {
      flex: 1;
      h3 {
        font-size: 14px;
        margin-bottom: 5px;
      }
      .sku {
        font-size: 12px;
        color: #999;
      }
    }

    .item-price {
      font-weight: bold;
    }
  }
}

@media (max-width: 768px) {
  .item-price {
    font-size: .4rem;
  }
}

.summary-card {
  background: var(--text-white);
  border-radius: 8px;
  padding: 25px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  position: sticky;
  top: 100px;

  h2 {
    font-size: 18px;
    margin-bottom: 20px;
  }

  .summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
    font-size: 14px;
    color: var(--text-color);

    &.total {
      font-size: 18px;
      font-weight: bold;
      color: #000;
      margin-top: 15px;
      margin-bottom: 25px;
    }
  }

  .divider {
    height: 1px;
    background-color: var(--primary-lighter);
    margin: 15px 0;
  }

  .btn-submit {
    width: 100%;
    background-color: var(--primary-color);
    border: none;
    
    &:hover {
      background-color: var(--primary-dark);
    }
    
    &:disabled {
      background-color: #ccc;
    }
  }
}

// Address Dialog
.address-dialog-content {
  .address-list {
    max-height: 400px;
    overflow-y: auto;
    margin-bottom: 20px;
    
    .address-item {
      border: 2px solid #eee;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 10px;
      cursor: pointer;
      transition: all 0.3s;
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      
      &:hover {
        border-color: #409eff;
        background-color: #f0f7ff;
      }
      
      &.selected {
        border-color: #409eff;
        background-color: #e6f2ff;
      }
      
      .address-info {
        flex: 1;
        
        .name-row {
          margin-bottom: 8px;
          display: flex;
          align-items: center;
          
          .name {
            font-weight: bold;
            font-size: 16px;
            margin-right: 15px;
          }
          
          .phone {
            color: #666;
            font-size: 14px;
          }
        }
        
        .email {
          color: #666;
          font-size: 14px;
          margin-bottom: 8px;
        }
        
        .address-text {
          color: #555;
          line-height: 1.5;
          margin-bottom: 5px;
        }
        
        .city-postal {
          color: #666;
          font-size: 14px;
          margin-top: 5px;
        }
      }
      
      .address-actions {
        display: flex;
        gap: 10px;
        margin-left: 15px;
      }
    }
  }
  
  .add-address-btn {
    width: 100%;
  }
  
  .empty-address {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 40px 20px;
    
    .empty-icon {
      margin-bottom: 20px;
      opacity: 0.6;
    }
    
    .empty-text {
      color: #909399;
      font-size: 14px;
      margin-bottom: 20px;
    }
  }
}

@media (max-width: 768px) {
  @import "@/assets/scss/module/m_order_create.scss";
}
</style>


