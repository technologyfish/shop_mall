<template>
  <div class="product-detail-page">
    <div class="container" v-if="product">
<!--      <div class="breadcrumb">-->
<!--        <router-link to="/">Home</router-link> / -->
<!--        <router-link to="/shop">Shop</router-link> / -->
<!--        <span>{{ product.name }}</span>-->
<!--      </div>-->

      <div class="product-detail-wrapper">
        <div class="product-gallery">
          <div class="main-image">
            <img :src="currentImage" :alt="product.name" />
          </div>
          <!-- 缩略图列表 -->
          <div class="thumbnail-list" v-if="imageList.length > 1">
            <div 
              v-for="(img, index) in imageList" 
              :key="index"
              class="thumbnail-item"
              :class="{ active: currentImage === img }"
              @click="currentImage = img"
            >
              <img :src="img" :alt="`${product.name} - ${index + 1}`" />
            </div>
          </div>
        </div>

        <div class="product-info-col">
          <h1 class="product-title">{{ product.name }}</h1>
          <div class="product-price">£{{ product.price }}</div>
          
          <div class="product-description">
            <p>{{ product.description }}</p>
          </div>

          <div class="product-meta">
            <div class="meta-item" v-if="product.stock">
              <span class="label">Stock:</span>
              <span class="value">{{ product.stock }}</span>
            </div>
            <div class="meta-item" v-if="product.shipping_fee > 0">
              <span class="label">Shipping:</span>
              <span class="value">
                £{{ product.shipping_fee }}
                <span v-if="product.free_shipping_threshold > 0" style="color: #67c23a; font-size: 12px;">
                  (Free shipping over £{{ product.free_shipping_threshold }})
                </span>
              </span>
            </div>
            <div class="meta-item" v-else-if="product.shipping_fee === 0">
              <span class="label">Shipping:</span>
              <span class="value" style="color: #67c23a;">Free Shipping</span>
            </div>
          </div>

          <div class="purchase-actions">
            <div class="quantity-wrapper">
              <label>Quantity:</label>
              <el-input-number v-model="quantity" :min="1" :max="product.stock || 100" />
            </div>

            <div class="order-summary">
              <div class="summary-row">
                <span>Subtotal:</span>
                <span>£{{ (product.price * quantity).toFixed(2) }}</span>
              </div>
              <div class="summary-row" v-if="shippingSettings && shippingSettings.shipping_fee > 0 && (product.price * quantity) < (shippingSettings.free_shipping_threshold || 0)">
                <span>Shipping:</span>
                <span>£{{ shippingSettings.shipping_fee }}</span>
              </div>
              <div class="summary-row" v-else-if="shippingSettings && shippingSettings.shipping_fee > 0 && shippingSettings.free_shipping_threshold && (product.price * quantity) >= shippingSettings.free_shipping_threshold">
                <span>Shipping:</span>
                <span style="color: #67c23a;">Free</span>
              </div>
              <div class="summary-row discount" v-if="hasFirstOrderDiscount && discountPercent > 0">
                <span>First Order Discount ({{ discountPercent }}%):</span>
                <span style="color: #67c23a;">-£{{ calculateDiscount() }}</span>
              </div>
              <div class="summary-row total">
                <span>Total:</span>
                <span>£{{ calculateTotal() }}</span>
              </div>
            </div>

            <div class="btn-group">
              <button class="btn-add-cart" @click="handleAddToCart" :disabled="loading">
                Add to Cart
              </button>
              <button class="btn-checkout" @click="handleBuyNow" :disabled="loading">
                Buy It Now
              </button>
            </div>
          </div>

          <div class="product-content" v-if="product.content" v-html="product.content"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import message from '@/utils/message'
import { getProduct } from '@/api/product'
import { getShippingSettings } from '@/api/shipping'
import { useCartStore } from '@/store/cart'
import { useUserStore } from '@/store/user'
import { getImageUrl } from '@/utils/image'

const route = useRoute()
const router = useRouter()
const cartStore = useCartStore()
const userStore = useUserStore()

const product = ref(null)
const quantity = ref(1)
const loading = ref(false)
const currentImage = ref('')
const imageList = ref([])
const shippingSettings = ref(null)
const hasFirstOrderDiscount = ref(false)
const discountPercent = ref(0)

onMounted(async () => {
  try {
    // 获取商品信息
    const res = await getProduct(route.params.id)
    product.value = res.data.data
    
    // 获取运费设置
    const shippingRes = await getShippingSettings()
    shippingSettings.value = shippingRes.data.data
    
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
    
    // 设置图片列表（使用完整 URL）
    if (product.value.images && Array.isArray(product.value.images) && product.value.images.length > 0) {
      imageList.value = product.value.images.map(img => getImageUrl(img))
      currentImage.value = getImageUrl(product.value.images[0])
    } else if (product.value.image) {
      imageList.value = [getImageUrl(product.value.image)]
      currentImage.value = getImageUrl(product.value.image)
    } else {
      imageList.value = ['/placeholder-product.jpg']
      currentImage.value = '/placeholder-product.jpg'
    }
  } catch (error) {
    console.error('Fetch product error:', error)
  }
})

const handleAddToCart = async () => {
  if (!userStore.isLoggedIn) {
    message.warning('Please login first')
    router.push('/login')
    return
  }

  loading.value = true
  try {
    await cartStore.addItem(product.value.id, quantity.value)
    // message.success('Added to cart')
  } catch (error) {
    message.error(error.message || 'Failed to add to cart')
  } finally {
    loading.value = false
  }
}

// 计算折扣金额
const calculateDiscount = () => {
  if (!product.value || !hasFirstOrderDiscount.value) return '0.00'
  const subtotal = parseFloat(product.value.price) * parseInt(quantity.value)
  const discount = subtotal * (discountPercent.value / 100)
  return discount.toFixed(2)
}

// 计算总价
const calculateTotal = () => {
  if (!product.value) return '0.00'
  
  const subtotal = parseFloat(product.value.price) * parseInt(quantity.value)
  let shippingFee = 0
  let discount = 0
  
  // 使用统一的运费设置
  if (shippingSettings.value && shippingSettings.value.shipping_fee && parseFloat(shippingSettings.value.shipping_fee) > 0) {
    // 如果有免运费门槛且达到门槛
    if (shippingSettings.value.free_shipping_threshold && 
        parseFloat(shippingSettings.value.free_shipping_threshold) > 0 && 
        subtotal >= parseFloat(shippingSettings.value.free_shipping_threshold)) {
      shippingFee = 0
    } else {
      shippingFee = parseFloat(shippingSettings.value.shipping_fee)
    }
  }
  
  // 首单折扣
  if (hasFirstOrderDiscount.value && discountPercent.value > 0) {
    discount = subtotal * (discountPercent.value / 100)
  }
  
  const total = subtotal + shippingFee - discount
  return total.toFixed(2)
}

const handleBuyNow = async () => {
  if (!userStore.isLoggedIn) {
    message.warning('Please login first')
    router.push('/login')
    return
  }
  
  // Buy Now: 直接购买，不经过购物车，只购买当前商品
  loading.value = true
  try {
    // 传递商品信息到订单创建页面
    router.push({
      path: '/orders/create',
      query: {
        type: 'buynow',
        productId: product.value.id,
        quantity: quantity.value
      }
    })
  } catch (error) {
    message.error(error.message || 'Failed to proceed')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped lang="scss">
.product-detail-page {
  min-height: 100vh;
  padding-top: 40px;
  background-color: var(--primary-lighter);
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px 80px;
}

.breadcrumb {
  margin-bottom: 40px;
  color: #666;
  font-size: 14px;
  
  a {
    color: #666;
    text-decoration: none;
    &:hover { color: #000; }
  }
}

.product-detail-wrapper {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 60px;
  
  @media (max-width: 768px) {
    grid-template-columns: 1fr;
    gap: 30px;
  }
}

.product-gallery {
  .main-image {
    background-color: transparent;
    border-radius: 12px;
    overflow: hidden;
    padding: 20px;
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 20px;
    
    img {
      border-radius: 12px;
      width: 100%;
      height: 100%;
      max-height: 500px;
      object-fit: cover;
      transition: all 0.3s ease;
    }
  }

  .thumbnail-list {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    
    .thumbnail-item {
      width: 80px;
      height: 80px;
      border-radius: 8px;
      overflow: hidden;
      cursor: pointer;
      border: 2px solid transparent;
      transition: all 0.3s ease;
      background-color: #f9f9f9;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 5px;
      
      &:hover {
        border-color: #999;
      }
      
      &.active {
        border-color: var(--primary-color);
      }
      
      img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        border-radius: 15px;
      }
    }
  }
}

.product-info-col {
  .product-title {
    color: var(--text-white);
    font-size: 32px;
    font-weight: bold;
    margin-bottom: 20px;
    font-family: 'Courier New', serif;
  }

  .product-price {
    color: var(--text-white);
    font-size: 24px;
    margin-bottom: 20px;
    font-weight: 600;
  }

  .product-description {
    font-size: 16px;
    line-height: 1.6;
    color: var(--secondary-color);
    margin-bottom: 30px;
  }

  .product-meta {
    margin-bottom: 30px;
    
    .meta-item {
      margin-bottom: 10px;
      font-size: 14px;
      
      .label {
        font-weight: bold;
        margin-right: 10px;
        color: var(--text-white);
      }
      .value {
        color: var(--text-white);
      }
    }
  }

  .purchase-actions {
    margin-bottom: 40px;

    .quantity-wrapper {
      margin-bottom: 20px;
      display: flex;
      align-items: center;

      label {
        color: var(--text-white);
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
        font-size: 14px;
        margin-right: 10px;
      }
    }

    .order-summary {
      background-color: #f9f9f9;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 20px;

      .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        font-size: 14px;

        &.total {
          font-size: 18px;
          font-weight: bold;
          padding-top: 10px;
          border-top: 1px solid #ddd;
          margin-top: 10px;
        }

        span:last-child {
          font-weight: 600;
        }
      }
    }

    .btn-group {
      display: flex;
      flex-direction: column;
      gap: 15px;

      button {
        width: 100%;
        padding: 15px;
        border-radius: 30px;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s;
        border: none;
        
        &.btn-add-cart {
          background-color: var(--secondary-color);
          color: #000;
          font-weight: bold;
        }

        &.btn-checkout {
          background-color: var(--primary-color);
          color: #fff;
          

        }
        
        &:disabled {
          opacity: 0.7;
          cursor: not-allowed;
        }
      }
    }
  }
  
  .product-content {
    margin-top: 40px;
    border-top: 1px solid #eee;
    padding-top: 20px;
    line-height: 1.8;
  }
}

@media (max-width: 768px) {
  @import "@/assets/scss/module/m_product_detail.scss";
}
</style>


