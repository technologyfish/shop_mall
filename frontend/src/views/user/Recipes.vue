<template>
  <div class="recipes-page">
    <!-- Loading 弹窗 -->
    <PageLoading :visible="loading" text="Loading recipes..." />

    <!-- Category Sections -->
    <div v-for="category in categories" :key="category.id" class="category-section">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">{{ category.name }}</h2>
          <p class="section-desc">{{ category.description }}</p>
        </div>

        <div class="recipes-grid">
          <div 
            v-for="recipe in category.recipes" 
            :key="recipe.id"
            class="recipe-card"
          >
            <div class="recipe-image">
              <img :src="getImageUrl(recipe.image, '/placeholder-recipe.jpg')" :alt="recipe.title" />
            </div>
            <div class="recipe-content">
              <h3 class="recipe-title">{{ recipe.title }}</h3>
              <p class="recipe-desc">{{ recipe.description || recipe.subtitle }}</p>
              
              <div class="recipe-meta">
                <span class="meta-item" v-if="recipe.prep_time">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Prep: {{ recipe.prep_time }} mins
                </span>
                <span class="meta-item" v-if="recipe.cook_time">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z" />
                  </svg>
                  Cook: {{ recipe.cook_time }} mins
                </span>
                <span class="meta-item" v-if="recipe.servings">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                  </svg>
                  {{ recipe.servings }} servings
                </span>
              </div>

              <router-link :to="`/recipe/${recipe.id}`" class="view-recipe-link">
                View full recipe
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </svg>
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import message from '@/utils/message'
import { getImageUrl } from '@/utils/image'
import { getRecipeCategories } from '@/api/recipe'
import { getArticleDetail } from '@/api/article'
import PageLoading from '@/components/PageLoading.vue'

const categories = ref([])
const article = ref(null)
const email = ref('')
const loading = ref(true)

// 获取分类及其菜谱
const fetchCategories = async () => {
  try {
    const res = await getRecipeCategories()
    categories.value = res.data.data || []
  } catch (error) {
    console.error('Failed to load categories:', error)
  }
}

// 获取文章ID 5
const fetchArticle = async () => {
  try {
    const res = await getArticleDetail(5)
    article.value = res.data.data
  } catch (error) {
    console.error('Failed to load article:', error)
    // 如果文章不存在也不影响页面显示
  }
}

// 订阅
const handleSubscribe = () => {
  message.success('Thank you for subscribing!')
  email.value = ''
}

// 加载数据
const loadData = async () => {
  loading.value = true
  try {
    await Promise.all([fetchCategories(), fetchArticle()])
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>

<style lang="scss" scoped>
.recipes-page {
  min-height: 100vh;
  background: $journey-color;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

// Category Section
.category-section {
  padding: 60px 0;
  background: #F5F5F0;
  
  &:nth-child(even) {
    background: #fff;
  }

  .section-header {
    text-align: center;
    margin-bottom: 50px;

    .section-title {
      font-size: 36px;
      font-weight: bold;
      color: var(--accent-color);
      margin-bottom: 15px;
      font-family: 'Courier New', serif;
    }

    .section-desc {
      font-size: 16px;
      color: #666;
      max-width: 600px;
      margin: 0 auto;
    }
  }

  .recipes-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
  }

  .recipe-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    transition: all 0.3s;

    &:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    }

    .recipe-image {
      width: 100%;
      height: 220px;
      overflow: hidden;

      img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s;
      }
    }

    &:hover .recipe-image img {
      transform: scale(1.05);
    }

    .recipe-content {
      padding: 25px;

      .recipe-title {
        font-size: 20px;
        font-weight: bold;
        color: #333;
        margin-bottom: 12px;
        line-height: 1.3;
      }

      .recipe-desc {
        font-size: 14px;
        color: #666;
        line-height: 1.6;
        margin-bottom: 16px;
        height: 66px;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
      }

      .recipe-meta {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 16px;
        padding-bottom: 16px;
        border-bottom: 1px solid #eee;

        .meta-item {
          font-size: 13px;
          color: #666;
          display: flex;
          align-items: center;
          gap: 6px;

          svg {
            width: 16px;
            height: 16px;
            color: var(--primary-color);
          }
        }
      }

      .view-recipe-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--primary-color);
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s;

        svg {
          width: 16px;
          height: 16px;
          transition: transform 0.3s;
        }

        &:hover {
          gap: 10px;
          
          svg {
            transform: translateX(4px);
          }
        }
      }
    }
  }
}

// Article Section
.article-section {
  background: var(--accent-color);
  color: white;
  padding: 80px 0 60px;

  .article-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
    margin-bottom: 60px;

    .article-image {
      border-radius: 12px;
      overflow: hidden;

      img {
        width: 100%;
        height: auto;
        display: block;
      }
    }

    .article-content {
      .article-title {
        font-size: 32px;
        font-weight: bold;
        margin-bottom: 20px;
        font-family: 'Courier New', serif;
      }

      .article-excerpt {
        font-size: 15px;
        line-height: 1.8;
        margin-bottom: 20px;
        opacity: 0.9;
      }

      .article-cta {
        font-size: 16px;
        line-height: 1.6;
      }
    }
  }

  .newsletter {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    text-align: center;

    h3 {
      font-size: 28px;
      font-weight: bold;
      margin-bottom: 15px;
      font-family: 'Courier New', serif;
    }

    p {
      font-size: 15px;
      opacity: 0.9;
      margin-bottom: 25px;
    }

    .newsletter-form {
      display: flex;
      max-width: 500px;
      margin: 0 auto;
      gap: 10px;

      input {
        flex: 1;
        padding: 12px 20px;
        border: none;
        border-radius: 6px;
        font-size: 14px;

        &:focus {
          outline: none;
        }
      }

      button {
        padding: 12px 30px;
        background: #8B4513;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.3s;

        &:hover {
          background: darken(#8B4513, 10%);
        }
      }
    }
  }
}

@media (max-width: 968px) {
  .category-section {
    padding: 40px 0;

    .section-header {
      margin-bottom: 30px;

      .section-title {
        font-size: 28px;
      }
    }

    .recipes-grid {
      grid-template-columns: 1fr;
      gap: 20px;
    }
  }

  .article-section {
    padding: 60px 0 40px;

    .article-container {
      grid-template-columns: 1fr;
      gap: 30px;
      margin-bottom: 40px;

      .article-content {
        .article-title {
          font-size: 24px;
        }
      }
    }

    .newsletter {
      .newsletter-form {
        flex-direction: column;

        button {
          width: 100%;
        }
      }
    }
  }
}

@media (max-width: 768px) {
  @import "@/assets/scss/module/m_recipes.scss";
}
</style>


