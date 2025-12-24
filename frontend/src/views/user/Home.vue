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

    <!-- Journey Section -->
    <section class="journey-section" v-if="journey">
      <div class="container">
        <div class="journey-image">
          <img :src="journey.image ? getImageUrl(journey.image) : '/placeholder-journey.jpg'" :alt="journey.title" />
        </div>
        <div class="journey-content">
          <h2 class="journey-title">{{ journey.title }}</h2>
          <p class="journey-subtitle">{{ journey.subtitle }}</p>
          <router-link to="/our-journey" class="btn-secondary">Our Story</router-link>
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

    <!-- Additional Articles -->
    <section class="articles-section" v-if="articles && articles.length">
      <article 
        v-for="(article, index) in articles" 
        :key="article.id"
        class="article-block"
        :class="{ 'reverse': index % 2 === 1 }"
        @click="goToArticle(article.id)"
      >
        <div class="container">
          <div class="article-image">
            <img :src="article.image ? getImageUrl(article.image) : '/placeholder-article.jpg'" :alt="article.title" />
          </div>
          <div class="article-content">
            <h2 class="article-title">{{ article.title }}</h2>
            <p class="article-subtitle" v-html="truncateContent(article.content, 200)"></p>
            <span class="read-more">Read More →</span>
          </div>
        </div>
      </article>
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
const journey = ref(null)
const featuredRecipes = ref([])
const articles = ref([])

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

// 获取首页数据
const fetchHomeData = async () => {
  try {
    loading.value = true
    
    // 基础数据请求（Banner、商品、食谱）
    const [bannerRes, productsRes, recipesRes] = await Promise.all([
      getBanners('home'),
      getProducts({ is_featured: 1, limit: 10 }),
      getRecipes({ is_featured: 1, limit: 10 })
    ])
    
    // 文章请求单独处理，避免一个失败导致全部失败
    const [article4, article3, article5] = await Promise.all([
      safeGetArticle(4),
      safeGetArticle(3),
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

    // 处理文章（只添加成功获取的文章）
    const articleList = []
    if (article4) articleList.push(article4)
    if (article3) articleList.push(article3)
    articles.value = articleList

    // 处理 Journey (获取ID为5的文章)
    if (article5) {
      journey.value = article5
    } else {
      // 默认 Journey 数据（如果后台未配置）
      journey.value = {
        title: 'The Chilli Trail Journey',
        subtitle: 'Born from our love of food and travel...',
        image: '/placeholder-journey.jpg'
      }
    }

  } catch (error) {
    console.error('获取首页数据失败:', error)
  } finally {
    loading.value = false
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

// 跳转文章详情
const goToArticle = (id) => {
  router.push(`/article/${id}`)
}

// 截断内容
const truncateContent = (content, maxLength) => {
  if (!content) return ''
  // 去除HTML标签
  const plainText = content.replace(/<[^>]+>/g, '')
  if (plainText.length <= maxLength) return plainText
  return plainText.substring(0, maxLength) + '...'
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
