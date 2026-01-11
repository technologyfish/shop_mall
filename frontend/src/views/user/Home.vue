<template>
  <div class="home-page">
    <!-- 首单优惠营销弹窗 -->
    <FirstOrderPromotion />
    
    <!-- Loading 弹窗 -->
    <PageLoading :visible="loading" text="Loading..." />
    
    <!-- Content -->
    <div v-show="!loading">
    <!-- Hero Banner -->
    <section class="hero-banner" v-if="banner">
      <div class="banner-content">
        <h1 class="title">{{ banner.title }}</h1>
        <p class="subtitle">{{ banner.subtitle }}</p>
        <router-link :to="banner.link || '/shop'" class="btn-shop">
          {{ banner.button_text || 'SHOP THE COLLECTION' }}
        </router-link>
      </div>
      <div class="banner-image" :style="{ backgroundImage: `url(${getImageUrl(banner.image)})` }"></div>
    </section>

    <!-- Featured Products -->
    <section class="featured-products">
      <h2 class="section-title">Featured Products</h2>
      
      <div class="products-swiper-container">
        <swiper
          :modules="modules"
          :slides-per-view="1"
          :space-between="20"
          :loop="true"
          :autoplay="{ delay: 3000, disableOnInteraction: false }"
          :navigation="{
            nextEl: '.products-button-next',
            prevEl: '.products-button-prev'
          }"
          :pagination="{
            el: '.products-pagination',
            clickable: true
          }"
          :breakpoints="{
            320: { slidesPerView: 1, spaceBetween: 20 },
            768: { slidesPerView: 2, spaceBetween: 20 },
            1024: { slidesPerView: 3, spaceBetween: 30 }
          }"
          class="products-swiper"
        >
          <swiper-slide v-for="(product, index) in featuredProducts" :key="product.id">
            <div class="product-card new-design" @click="goProduct(product.id)">
              <div class="product-image-only">
                <img :src="getProductImage(product)" :alt="product.name" />
              </div>
              <div class="card-bottom">
                <h3 class="product-name">{{ product.name }}</h3>
                <p class="product-desc">{{ product.description }}</p>
              </div>
            </div>
          </swiper-slide>
        </swiper>
        
        <!-- Custom Navigation & Pagination -->
        <div class="swiper-controls">
          <div class="products-button-prev nav-btn">
            <i class="arrow-left"></i>
          </div>
          <div class="products-pagination"></div>
          <div class="products-button-next nav-btn">
            <i class="arrow-right"></i>
          </div>
        </div>
      </div>
    </section>

    <!-- Join The Club Section -->
    <section class="join-club-section">
      <div class="club-container">
        <h2 class="club-title">HOW THE SAUCE CLUB SUBSCRIPTION WORKS</h2>
        
        <div class="club-steps">
          <!-- Step 1 -->
          <div class="club-step">
            <div class="step-number">1</div>
            <div class="step-icon">
              <img src="@/assets/images/sub-1.png" alt="Join the Club" />
            </div>
            <h3 class="step-title">JOIN THE CLUB</h3>
            <p class="step-desc">Sign up to join the sauciest club in the UK. Pause, skip or cancel anytime.</p>
          </div>

          <!-- Step 2 -->
          <div class="club-step">
            <div class="step-number">2</div>
            <div class="step-icon">
              <img src="@/assets/images/sub-2.png" alt="Unbox Your Sauce" />
            </div>
            <h3 class="step-title">UNBOX YOUR SAUCE</h3>
            <p class="step-desc">Every other month you'll receive a flavour packed delivery.</p>
          </div>

          <!-- Step 3 -->
          <div class="club-step">
            <div class="step-number">3</div>
            <div class="step-icon">
              <img src="@/assets/images/sub-3.png" alt="Savour the Flavour" />
            </div>
            <h3 class="step-title">SAVOUR THE FLAVOUR</h3>
            <p class="step-desc">Dip, drizzle and dunk like never before. Be sure to tag @thechillitrail when you've cracked open your new sauce.</p>
          </div>
        </div>

        <div class="club-cta">
          <router-link to="/subscription" class="btn-join-club">JOIN THE CLUB</router-link>
        </div>
      </div>
    </section>

    <!-- Featured Recipes -->
    <section class="featured-recipes">
      <h2 class="section-title">Featured Recipes</h2>
      
      <div class="recipes-swiper-container">
        <swiper
          :modules="modules"
          :slides-per-view="1"
          :space-between="20"
          :loop="true"
          :navigation="{
            nextEl: '.recipes-button-next',
            prevEl: '.recipes-button-prev'
          }"
          :pagination="{
            el: '.recipes-pagination',
            clickable: true
          }"
          :breakpoints="{
            320: { slidesPerView: 1, spaceBetween: 20 },
            768: { slidesPerView: 2, spaceBetween: 20 },
            1024: { slidesPerView: 3, spaceBetween: 30 },
          }"
          class="recipes-swiper"
        >
          <swiper-slide v-for="recipe in featuredRecipes" :key="recipe.id">
            <div class="recipe-card" @click="goRecipe(recipe.id)">
              <div class="recipe-image">
                <img :src="recipe.image ? getImageUrl(recipe.image) : '/placeholder-recipe.jpg'" :alt="recipe.title" />
              </div>
              <div class="recipe-info">
                <h3 class="recipe-title">{{ recipe.title }}</h3>
                <p class="recipe-subtitle">{{ recipe.subtitle }}</p>
                <button class="btn-view-recipe">View Recipe →</button>
              </div>
            </div>
          </swiper-slide>
        </swiper>

        <!-- Custom Navigation & Pagination -->
        <div class="swiper-controls recipes-controls">
          <div class="recipes-button-prev nav-btn">
            <i class="arrow-left"></i>
          </div>
          <div class="recipes-pagination"></div>
          <div class="recipes-button-next nav-btn">
            <i class="arrow-right"></i>
          </div>
        </div>
      </div>
    </section>

    <!-- Photos Gallery Section -->
    <section class="photos-gallery-section" v-if="homePhotos && homePhotos.length">

      <h2 class="section-title">Events</h2>


      <div class="photos-grid">
        <div 
          v-for="photo in homePhotos" 
          :key="photo.id" 
          class="photo-item"
          @click="goToPhotos"
        >
          <img :src="getImageUrl(photo.image)" :alt="photo.title || 'Gallery'" />
        </div>
      </div>
    </section>


    <!-- Journey Section -->
    <section class="journey-section" v-if="journey">
      <div class="container">
        <div class="journey-image">
          <img :src="journey.image ? getImageUrl(journey.image) : '/placeholder-journey.jpg'" :alt="journey.title" />
        </div>
        <div class="journey-content">
          <h2 class="journey-title">{{ journey.title }}</h2>
          <p class="journey-subtitle">{{ journey.subtitle }}</p>
<!--          <router-link to="/our-journey" class="btn-secondary">Our Story</router-link>-->
        </div>
      </div>
    </section>


    </div><!-- end content -->
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Swiper, SwiperSlide } from 'swiper/vue'
import { Autoplay, Navigation, Pagination } from 'swiper/modules'
import { getBanners } from '@/api/home'
import { getProducts } from '@/api/product'
import { getRecipes } from '@/api/recipe'
import { getPhotos } from '@/api/photo'
import { getArticleDetail } from '@/api/article'
import PageLoading from '@/components/PageLoading.vue'
import FirstOrderPromotion from '@/components/FirstOrderPromotion.vue'
import { getImageUrl, getProductImage } from '@/utils/image'
import 'swiper/css'
import 'swiper/css/navigation'
import 'swiper/css/pagination'

const router = useRouter()
const modules = [Autoplay, Navigation, Pagination]

// 数据
const loading = ref(true)
const banner = ref(null)
const featuredProducts = ref([])
const featuredRecipes = ref([])
const homePhotos = ref([])
const journey = ref(null)
// 获取首页数据
const fetchHomeData = async () => {
  try {
    loading.value = true
    
    // 基础数据请求（Banner、商品、食谱、照片）
    const [bannerRes, productsRes, recipesRes, photosRes,article5] = await Promise.all([
      getBanners('home'),
      getProducts({ is_featured: 1, limit: 10 }),
      getRecipes({ is_featured: 1, limit: 10 }),
      getPhotos({ limit: 3 }),
      safeGetArticle(5)
    ])
    
    // 处理 Banner
    if (bannerRes.data && bannerRes.data.data && bannerRes.data.data.length > 0) {
      banner.value = bannerRes.data.data[0]
    }

    // 处理商品 (分页数据)
    if (productsRes.data && productsRes.data.data && productsRes.data.data.data) {
      featuredProducts.value = productsRes.data.data.data
    }

    // 处理食谱 (分页数据)
    if (recipesRes.data && recipesRes.data.data && recipesRes.data.data.data) {
      featuredRecipes.value = recipesRes.data.data.data
    }

    // 处理照片（只取前3张）
    if (photosRes.data && photosRes.data.data) {
      const photosData = photosRes.data.data.data || photosRes.data.data
      homePhotos.value = Array.isArray(photosData) ? photosData.slice(0, 3) : []
    }

    journey.value = article5

  } catch (error) {
    console.error('获取首页数据失败:', error)
  } finally {
    loading.value = false
  }
}
// 安全获取文章（失败时返回null）
const safeGetArticle = async (id) => {
  try {
    const res = await getArticleDetail(id)
    return res.data?.data || null
  } catch (error) {
    console.warn(`Failed to load article ${id}:`, error)
    return null
  }
}


// 跳转商品详情
const goProduct = (id) => {
  router.push(`/product/${id}`)
}

// 跳转食谱详情
const goRecipe = (id) => {
  router.push(`/recipe/${id}`)
}

// 跳转照片页面
const goToPhotos = () => {
  router.push('/photos')
}

onMounted(() => {
  fetchHomeData()
})
</script>

<style lang="scss">
// Loading样式
.page-loading {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: var(--primary-lighter);
  
  .loading-spinner {
    width: 60px;
    height: 60px;
    border: 4px solid rgba(0, 0, 0, 0.1);
    border-left-color: var(--primary-color);
    border-radius: 50%;
    animation: spin 1s linear infinite;
  }
  
  .loading-text {
    margin-top: 20px;
    font-size: 16px;
    color: var(--text-color);
    font-weight: 500;
  }
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

@import "@/assets/scss/module/home.scss";

@media (max-width: 768px) {
  @import "@/assets/scss/module/m_home.scss";
  
  .page-loading {
    .loading-spinner {
      width: 80px;
      height: 80px;
      border-width: 6px;
    }
    
    .loading-text {
      font-size: 18px;
      margin-top: 30px;
    }
  }
}
</style>
