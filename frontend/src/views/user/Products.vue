<template>
  <div class="products-page">
    <!-- Loading 弹窗 -->
    <PageLoading :visible="loading" text="Loading products..." />

    <div class="header-section">
      <h1>Shop All Products</h1>
    </div>

    <div class="container">
      <div class="product-grid" v-if="products.length">
        <div 
          v-for="(product, index) in products" 
          :key="product.id"
          class="product-card"
          :class="`bg-style-${index % 4}`"
          @click="gotoDetail(product.id)"
        >
          <div class="card-top">
            <div class="product-image">
              <img :src="getProductImage(product)" :alt="product.name" />
            </div>
          </div>
          <div class="card-bottom">
            <h3>{{ product.name }}</h3>
            <p class="price">£{{ product.price }}</p>
            <button class="btn-view">View Product</button>
          </div>
        </div>
      </div>
      <el-empty v-else description="暂无商品" />

      <div class="pagination">
        <el-pagination
          class="my-pagination"
          v-model:current-page="searchParams.page"
          v-model:page-size="searchParams.limit"
          :total="total"
          layout="prev, pager, next"
          @current-change="fetchProducts"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { getProducts } from '@/api/product'
import PageLoading from '@/components/PageLoading.vue'
import { getProductImage } from '@/utils/image'

const router = useRouter()
const route = useRoute()
const products = ref([])
const total = ref(0)
const loading = ref(true)

const searchParams = reactive({
  page: 1,
  limit: 12,
  status: 1 // Only active products
})

onMounted(async () => {
  await fetchProducts()
})

const fetchProducts = async () => {
  loading.value = true
  try {
    const res = await getProducts(searchParams)
    products.value = res.data.data.data || []
    total.value = res.data.data.total || 0
  } catch (error) {
    console.error('Fetch products error:', error)
  } finally {
    loading.value = false
  }
}

const gotoDetail = (id) => {
  router.push(`/product/${id}`)
}
</script>

<style scoped lang="scss">
.products-page {
  min-height: 100vh;
  background-color: var(--primary-lighter)
}

.header-section {
  text-align: center;
  padding: 40px 0;
  
  h1 {
    font-family: 'Courier New', serif; // Use similar handwritten/serif font
    font-size: 36px;
    font-weight: bold;
    color: var(--text-white);
  }
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px 60px;
}

.product-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 30px;
  margin-bottom: 40px;

  @media (max-width: 1024px) {
    grid-template-columns: repeat(3, 1fr);
  }
  
  @media (max-width: 768px) {
    grid-template-columns: repeat(2, 1fr);
  }
  
  @media (max-width: 480px) {
    grid-template-columns: 1fr;
  }

  .product-card {
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    transition: transform 0.3s;
    background: #fff;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);

    &:hover {
      transform: translateY(-5px);
    }

    // Dynamic background colors for card top
    &.bg-style-0 .card-top { background-color: #E67E22; } // Orange
    &.bg-style-1 .card-top { background-color: var(--secondary-color); } // 流金黄
    &.bg-style-2 .card-top { background-color: var(--primary-color); } // 胭脂红
    &.bg-style-3 .card-top { background-color: var(--accent-color); } // 翠微绿

    .card-top {

      display: flex;
      align-items: center;
      justify-content: center;
      
      .product-image {
        width: 100%;
        height: 250px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        
        img {
          width: 100%;
          height: 100%;
          object-fit: cover;
          filter: drop-shadow(0 5px 15px rgba(0,0,0,0.2));
        }

        .subscription-badge {
          position: absolute;
          top: 10px;
          right: 10px;
          background-color: #e74c3c;
          color: #fff;
          padding: 5px 12px;
          border-radius: 20px;
          font-size: 12px;
          font-weight: bold;
          box-shadow: 0 2px 8px rgba(231, 76, 60, 0.3);
        }
      }
    }

    .card-bottom {
      padding: 20px;
      background: var(--primary-black);
      display: flex;
      flex-direction: column;
      gap: 10px;

      h3 {
        text-align: center;
        font-size: 16px;
        font-weight: bold;
        color: var(--text-white);
        margin: 0;
        line-height: 1.4;
        min-height: 44px; // 2 lines
      }

      .price {
        text-align: center;
        font-size: 18px;
        font-weight: bold;
        color: var(--text-white);
        margin: 0;

        .period {
          font-size: 14px;
          color: var(--text-white);
          font-weight: normal;
        }
      }

      .btn-view {
        width: 100%;
        padding: 12px;
        background-color: var(--primary-color);
        color: #fff;
        border: none;
        border-radius: 30px;
        font-size: 14px;
        font-weight: bold;
        cursor: pointer;
        transition: background 0.3s;
        margin-top: 10px;

        &:hover {
          background-color: var(--primary-dark);
        }
      }
    }
  }
}

.pagination {
  display: flex;
  justify-content: center;
  margin-top: 40px;
}
:deep().my-pagination{
  background-color: #f5f5f5;
  .btn-prev,.btn-next{
    background: transparent !important;
  }
}

@media (max-width: 768px) {
  @import "@/assets/scss/module/m_products.scss";
}
</style>


