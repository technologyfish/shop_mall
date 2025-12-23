<template>
  <div class="article-detail-page">
    <PageLoading :visible="loading" text="Loading article..." />

    <div class="container" v-if="article && !loading">
      <div class="article-header">
        <h1 class="article-title">{{ article.title }}</h1>
        <p class="article-subtitle" v-if="article.subtitle">{{ article.subtitle }}</p>
        <div class="article-meta">
          <span v-if="article.created_at">{{ formatDate(article.created_at) }}</span>
        </div>
      </div>

      <div class="article-cover" v-if="article.image">
        <img :src="getImageUrl(article.image)" :alt="article.title" />
      </div>

      <div class="article-content" v-html="article.content"></div>

      <div class="article-footer">
        <el-button @click="goBack" type="primary" plain>
          ← Back
        </el-button>
      </div>
    </div>

<!--    <div class="container" v-if="!article && !loading">-->
<!--      <el-empty description="Article not found">-->
<!--        <el-button type="primary" @click="goBack">Go Back</el-button>-->
<!--      </el-empty>-->
<!--    </div>-->
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { getImageUrl } from '@/utils/image'
import { getArticleDetail } from '@/api/article'
import PageLoading from '@/components/PageLoading.vue'

const route = useRoute()
const router = useRouter()

const article = ref(null)
const loading = ref(true)

const fetchArticle = async () => {
  loading.value = true
  try {
    const res = await getArticleDetail(route.params.id)
    article.value = res.data.data
  } catch (error) {
    console.error('Failed to load article:', error)
  } finally {
    loading.value = false
  }
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const goBack = () => {
  router.back()
}

onMounted(() => {
  fetchArticle()
})
</script>

<style scoped lang="scss">
.article-detail-page {
  min-height: 80vh;
  padding: 40px 0 60px;
  background: $journey-color;

  .container {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 20px;
  }

  .article-header {
    text-align: center;
    margin-bottom: 40px;

    .article-title {
      font-size: 42px;
      font-weight: 700;
      color: var(--accent-color);
      margin-bottom: 15px;
      line-height: 1.3;
    }

    .article-subtitle {
      font-size: 20px;
      color: #666;
      margin-bottom: 20px;
    }

    .article-meta {
      font-size: 14px;
      color: #999;
    }
  }

  .article-cover {
    margin-bottom: 40px;
    border-radius: 12px;
    overflow: hidden;

    img {
      width: 100%;
      height: auto;
      display: block;
    }
  }

  .article-content {
    background: white;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    line-height: 1.8;
    font-size: 16px;
    color: #333;

    :deep(h1),
    :deep(h2),
    :deep(h3),
    :deep(h4) {
      margin-top: 30px;
      margin-bottom: 15px;
      color: var(--accent-color);
    }

    :deep(p) {
      margin-bottom: 20px;
    }

    :deep(img) {
      max-width: 100%;
      height: auto;
      border-radius: 8px;
      margin: 20px 0;
    }

    :deep(ul),
    :deep(ol) {
      margin-bottom: 20px;
      padding-left: 30px;
    }

    :deep(li) {
      margin-bottom: 10px;
    }

    :deep(blockquote) {
      border-left: 4px solid var(--primary-color);
      padding-left: 20px;
      margin: 20px 0;
      font-style: italic;
      color: #666;
    }
  }

  .article-footer {
    margin-top: 40px;
    text-align: center;
  }

  @media (max-width: 768px) {
    padding: 20px 0 40px;

    .article-header {
      .article-title {
        font-size: 28px;
      }

      .article-subtitle {
        font-size: 16px;
      }
    }

    .article-content {
      padding: 20px;
      font-size: 15px;
    }
  }
}
</style>




