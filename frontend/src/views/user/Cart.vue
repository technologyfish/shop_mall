<template>
  <div class="cart-page">
    <div class="container">
      <h1 class="page-title">Shopping Cart</h1>

      <div v-if="cartStore.cartItems.length" class="cart-content">
        <!-- 全选 -->
        <div class="cart-header">
          <el-checkbox 
            v-model="allSelected" 
            @change="handleSelectAll"
            :indeterminate="isIndeterminate"
          >
            Select All
          </el-checkbox>
          <el-button 
            v-if="selectedIds.length > 0"
            type="danger" 
            text 
            @click="handleDeleteSelected"
          >
            Delete Selected ({{ selectedIds.length }})
          </el-button>
        </div>

        <!-- 商品列表 -->
        <div class="cart-items">
          <div 
            v-for="item in cartStore.cartItems" 
            :key="item.id" 
            class="cart-item"
          >
            <el-checkbox 
              :model-value="isItemSelected(item.id)"
              @change="(val) => handleSelectItem(item.id, val)"
            />
            
            <img 
              :src="getImageUrl(item.product?.image || item.product?.main_image, '/placeholder-product.jpg')" 
              class="product-img" 
            />
            
            <div class="product-info">
              <div class="product-name">{{ item.product?.name }}</div>
              <div class="product-price">£{{ item.product?.price }}</div>
            </div>
            
            <div class="quantity-control">
              <el-input-number
                :model-value="item.quantity"
                :min="1"
                :max="item.product?.stock"
                @change="(val) => handleUpdateQuantity(item, val)"
              />
            </div>
            
            <div class="item-total">
              £{{ (item.product?.price * item.quantity).toFixed(2) }}
            </div>
            
            <el-button 
              type="danger" 
              text 
              @click="handleDelete(item.id)"
            >
              Delete
            </el-button>
          </div>
        </div>

        <!-- 底部结算 -->
        <div class="cart-footer">
          <div class="footer-left">
            <span>Selected {{ selectedIds.length }} item(s)</span>
          </div>
          
          <div class="footer-right">
            <div class="price-summary">
              <div class="price-row">
                <span class="sub-total">Subtotal:</span>
                <span>£{{ selectedSubtotal.toFixed(2) }}</span>
              </div>
              <div class="price-row" v-if="shippingFee > 0">
                <span>Shipping:</span>
                <span>£{{ shippingFee.toFixed(2) }}</span>
              </div>
              <div class="price-row" v-else-if="shippingSettings && shippingSettings.shipping_fee > 0 && selectedSubtotal > 0">
                <span>Shipping:</span>
                <span style="color: #67c23a;">Free</span>
              </div>
              <div class="price-row discount" v-if="hasFirstOrderDiscount && discountAmount > 0">
                <span>First Order Discount ({{ discountPercent }}%):</span>
                <span style="color: #67c23a;">-£{{ discountAmount.toFixed(2) }}</span>
              </div>
              <div class="price-row total">
                <span>Total:</span>
                <span class="amount">£{{ selectedTotal.toFixed(2) }}</span>
              </div>
            </div>
            <el-button
              class="btn-checkout"
              type="primary" 
              size="large" 
              @click="handleCheckout"
              :disabled="selectedIds.length === 0"
            >
              Checkout
            </el-button>
          </div>
        </div>
      </div>
      
      <el-empty v-else description="Your cart is empty" />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessageBox } from 'element-plus'
import message from '@/utils/message'
import { getImageUrl } from '@/utils/image'
import { useCartStore } from '@/store/cart'
import { useUserStore } from '@/store/user'
import { deleteSelectedCarts } from '@/api/cart'
import { getShippingSettings } from '@/api/shipping'

const router = useRouter()
const cartStore = useCartStore()
const userStore = useUserStore()
const selectedIds = ref([])
const shippingSettings = ref(null)
const hasFirstOrderDiscount = ref(false)
const discountPercent = ref(0)

onMounted(async () => {
  await cartStore.fetchCart()
  // 获取运费设置
  try {
    const res = await getShippingSettings()
    shippingSettings.value = res.data.data
  } catch (error) {
    console.error('Failed to load shipping settings:', error)
  }
  
  // 检查首单优惠
  if (userStore.isLoggedIn) {
    await userStore.fetchUserInfo()
    if (userStore.userInfo?.first_order_discount === 1) {
      try {
        const promoRes = await fetch('/api/off-code/promotion')
        const promoData = await promoRes.json()
        if (promoData.data?.active) {
          hasFirstOrderDiscount.value = true
          discountPercent.value = promoData.data.discount_value || 10
        }
      } catch (e) {
        console.error('Failed to get promotion info:', e)
      }
    }
  }
})

// 是否全选
const allSelected = computed({
  get() {
    return cartStore.cartItems.length > 0 && 
           selectedIds.value.length === cartStore.cartItems.length
  },
  set(val) {
    // 由handleSelectAll处理
  }
})

// 是否半选状态
const isIndeterminate = computed(() => {
  return selectedIds.value.length > 0 && 
         selectedIds.value.length < cartStore.cartItems.length
})

// 选中商品的小计
const selectedSubtotal = computed(() => {
  return cartStore.cartItems
    .filter(item => selectedIds.value.includes(item.id))
    .reduce((total, item) => {
      return total + (item.product?.price * item.quantity)
    }, 0)
})

// 计算运费
const shippingFee = computed(() => {
  if (!shippingSettings.value || selectedSubtotal.value === 0) return 0
  
  const settings = shippingSettings.value
  if (!settings.shipping_fee || parseFloat(settings.shipping_fee) === 0) {
    return 0
  }
  
  // 如果有免运费门槛且达到门槛
  if (settings.free_shipping_threshold && 
      parseFloat(settings.free_shipping_threshold) > 0 && 
      selectedSubtotal.value >= parseFloat(settings.free_shipping_threshold)) {
    return 0
  }
  
  return parseFloat(settings.shipping_fee)
})

// 计算折扣金额
const discountAmount = computed(() => {
  if (!hasFirstOrderDiscount.value || discountPercent.value <= 0) return 0
  return selectedSubtotal.value * (discountPercent.value / 100)
})

// 总计（含运费和折扣）
const selectedTotal = computed(() => {
  return selectedSubtotal.value + shippingFee.value - discountAmount.value
})

// 检查商品是否被选中
const isItemSelected = (itemId) => {
  return selectedIds.value.includes(itemId)
}

// 全选/取消全选
const handleSelectAll = (val) => {
  if (val) {
    selectedIds.value = cartStore.cartItems.map(item => item.id)
  } else {
    selectedIds.value = []
  }
}

// 单选商品
const handleSelectItem = (itemId, val) => {
  if (val) {
    if (!selectedIds.value.includes(itemId)) {
      selectedIds.value.push(itemId)
    }
  } else {
    const index = selectedIds.value.indexOf(itemId)
    if (index > -1) {
      selectedIds.value.splice(index, 1)
    }
  }
}

// 更新数量
const handleUpdateQuantity = async (item, newQuantity) => {
  try {
    await cartStore.updateItem(item.id, newQuantity)
     // message.success('Updated successfully')
  } catch (error) {
    message.error(error.message || 'Update failed')
    // 刷新购物车以恢复原数量
    cartStore.fetchCart()
  }
}

// 删除单个商品
const handleDelete = async (id) => {
  try {
    await ElMessageBox.confirm('Are you sure you want to delete this item?', 'Confirm', {
      confirmButtonText: 'Confirm',
      cancelButtonText: 'Cancel',
      type: 'warning'
    })
    await cartStore.removeItem(id)
    // 从选中列表中移除
    const index = selectedIds.value.indexOf(id)
    if (index > -1) {
      selectedIds.value.splice(index, 1)
    }
    // message.success('Deleted successfully')
  } catch (error) {
    if (error !== 'cancel') {
      message.error(error.message || 'Delete failed')
    }
  }
}

// 批量删除选中商品
const handleDeleteSelected = async () => {
  if (selectedIds.value.length === 0) {
    message.warning('Please select items to delete')
    return
  }
  
  try {
    await ElMessageBox.confirm(
      `Delete ${selectedIds.value.length} selected item(s)?`, 
      'Confirm', 
      {
        confirmButtonText: 'Confirm',
        cancelButtonText: 'Cancel',
        type: 'warning'
      }
    )
    
    await deleteSelectedCarts(selectedIds.value)
    await cartStore.fetchCart()
    selectedIds.value = []
    // message.success('Deleted successfully')
  } catch (error) {
    if (error !== 'cancel') {
      message.error(error.message || 'Delete failed')
    }
  }
}

// 去结算
const handleCheckout = () => {
  if (selectedIds.value.length === 0) {
    message.warning('Please select items to checkout')
    return
  }
  
  // 将选中的商品ID存储到localStorage
  localStorage.setItem('checkout_cart_ids', JSON.stringify(selectedIds.value))
  router.push('/orders/create')
}
</script>

<style scoped lang="scss">
.cart-page {
  background-color: var(--primary-lighter);
  min-height: 80vh;
  padding: 40px 0;
  .page-title{
    color: var(--text-white)
  }
  .cart-content {
    background: var(--text-white);
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);

    .cart-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-bottom: 20px;
      border-bottom: 1px solid var(--primary-lighter);
      margin-bottom: 20px;
    }

    .cart-items {
      .cart-item {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 20px 0;
        border-bottom: 1px solid var(--primary-lighter);

        &:last-child {
          border-bottom: none;
        }

        .product-img {
          width: 100px;
          height: 100px;
          object-fit: cover;
          border-radius: 4px;
        }

        .product-info {
          flex: 1;

          .product-name {
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 8px;
            color: #333;
          }

          .product-price {
            font-size: 14px;
            color: var(--primary-lighter);
          }
        }

        .quantity-control {
          width: 120px;
        }

        .item-total {
          width: 100px;
          text-align: right;
          font-size: 18px;
          font-weight: 600;
          color: var(--primary-lighter);
        }
      }
    }

    .cart-footer {
      margin-top: 30px;
      padding-top: 20px;
      border-top: 2px solid var(--primary-lighter);
      display: flex;
      justify-content: space-between;
      align-items: center;

      .btn-checkout{
        background: var(--primary-lighter);
        color: var(--text-white);
        border: none;
      }
      .btn-checkout:disabled{
        background: #c5c5c5;
      }

      .footer-left {
        font-size: 14px;
        color: var(--primary-lighter);
      }

      .footer-right {
        display: flex;
        align-items: center;
        gap: 30px;

        .price-summary {
          .price-row {
            display: flex;
            justify-content: space-between;
            min-width: 200px;
            margin-bottom: 8px;
            font-size: 14px;

            .sub-total{
              font-weight: bold;
            }

            &.total {
              font-size: 18px;
              font-weight: 600;
              margin-top: 8px;
              padding-top: 8px;
              border-top: 1px solid var(--primary-lighter);

              .amount {
                color: var(--primary-lighter);
                font-size: 24px;
              }
            }
          }
        }
      }
    }
  }
}

// 移动端适配
@media (max-width: 768px) {
  .cart-page {
    padding: pxToVw(40) 0;

    .container {
      max-width: 100%;
      padding: 0 pxToVw(20);
    }

    .page-title {
      font-size: pxToVw(48);
      width: 98%;
      margin: 0 auto pxToVw(30) ;

    }

    .cart-content {
      width: 98%;
      padding: pxToVw(30) pxToVw(20);
      margin: 0 auto;

      .cart-header {
        flex-wrap: wrap;
        padding-bottom: pxToVw(15);
        margin-bottom: pxToVw(15);
      }

      .cart-items {
        .cart-item {
          flex-wrap: wrap;
          gap: pxToVw(10);
          padding: pxToVw(15) 0;
          position: relative;

          // 复选框独立一行
          .el-checkbox {
            position: absolute;
            top: pxToVw(55);
            left: 0;
          }

          // 图片和产品信息同行
          .product-img {
            width: pxToVw(80);
            height: pxToVw(80);
            margin-left: pxToVw(30); // 给复选框留空间
          }

          .product-info {
            flex: 1;
            min-width: 0;

            .product-name {
              font-size: pxToVw(14);
              margin-bottom: pxToVw(5);
              overflow: hidden;
              text-overflow: ellipsis;
              white-space: nowrap;
            }

            .product-price {
              font-size: 14px;
            }
          }

          // 数量控制和总价换行
          .quantity-control {
            width: pxToVw(100);
            margin-left: pxToVw(30); // 对齐图片
          }

          .item-total {
            width: auto;
            margin-left: auto;
            font-size: pxToVw(16);
          }

          // 删除按钮换行
          :deep(.el-button) {
            margin-left: pxToVw(30);
          }
        }
      }

      .cart-footer {
        flex-direction: column;
        align-items: stretch;
        gap: pxToVw(15);
        margin-top: pxToVw(20);
        padding: pxToVw(15) pxToVw(10);

        .footer-left {
          text-align: center;
        }

        .footer-right {
          flex-direction: column;
          gap: pxToVw(15);

          .price-summary {
            width: 100%;

            .price-row {
              min-width: unset;
              font-size: pxToVw(14);

              &.total {
                font-size: pxToVw(16);

                .amount {
                  font-size: pxToVw(20);
                }
              }
            }
          }

          .btn-checkout {
            width: 100%;
            height: pxToVw(88);
            font-size: pxToVw(32);
          }
        }
      }
    }
  }
}
</style>
