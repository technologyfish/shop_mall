<template>
  <div class="photos-page">
    <!-- Loading 弹窗 -->
    <PageLoading :visible="loading" text="Loading photos..." />

    <div class="container">
      <h1 class="page-title">Gallery</h1>

      <div class="photos-grid" v-if="photos.length">
        <div v-for="(photo, index) in photos" :key="photo.id" class="photo-item">
          <div class="photo-card" @click="showLightbox(index)">
            <img 
              :src="getImageUrl(photo.image, 'https://via.placeholder.com/400')" 
              :alt="photo.name"
              class="photo-image"
            />
          </div>
        </div>
      </div>

      <el-empty v-else description="暂无照片" />

      <!-- 分页 -->
      <div class="pagination" v-if="total > perPage">
        <el-pagination
          v-model:current-page="currentPage"
          :total="total"
          :page-size="perPage"
          layout="prev, pager, next"
          @current-change="fetchPhotos"
        />
      </div>
    </div>

    <!-- Lightbox -->
    <VueEasyLightbox
      :visible="lightboxVisible"
      :imgs="lightboxImages"
      :index="lightboxIndex"
      @hide="lightboxVisible = false"
      move-disabled
      scroll-disabled
      loop
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import message from '@/utils/message'
import { getPhotos } from '@/api/photo'
import PageLoading from '@/components/PageLoading.vue'
import { getImageUrl } from '@/utils/image'
import VueEasyLightbox from 'vue-easy-lightbox'

const photos = ref([])
const currentPage = ref(1)
const perPage = ref(12)
const total = ref(0)
const loading = ref(true)

// Lightbox 相关
const lightboxVisible = ref(false)
const lightboxIndex = ref(0)

// Lightbox 图片列表
const lightboxImages = computed(() => {
  return photos.value.map(p => ({
    src: getImageUrl(p.image, 'https://via.placeholder.com/400'),
    title: p.name || ''
  }))
})

// 显示 Lightbox
const showLightbox = (index) => {
  lightboxIndex.value = index
  lightboxVisible.value = true
}

onMounted(() => {
  fetchPhotos()
})

const fetchPhotos = async () => {
  loading.value = true
  try {
    const res = await getPhotos({
      page: currentPage.value,
      per_page: perPage.value
    })
    photos.value = res.data.data.data || []
    total.value = res.data.data.total || 0
  } catch (error) {
    message.error('获取照片失败')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped lang="scss">
.photos-page {
  min-height: 80vh;
  padding: 30px 0;
  background: $journey-color;

  .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
  }

  @media (max-width: 768px) {
    .container {
      width: 100%;
    }
  }

  .page-title {
    text-align: center;
    font-size: 48px;
    font-weight: bold;
    margin-bottom: 50px;
    color: var(--accent-color);
  }

  .photos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 30px;
    margin-bottom: 40px;

    @media (max-width: 768px) {
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 20px;
    }
  }

  .photo-item {
    .photo-card {
      background: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
      transition: transform 0.3s, box-shadow 0.3s;
      cursor: pointer;

      &:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
      }

      .photo-image {
        width: 100%;
        height: 300px;
        object-fit: cover;
        display: block;
      }
    }
  }

  .pagination {
    display: flex;
    justify-content: center;
    margin-top: 40px;
  }
}
</style>




