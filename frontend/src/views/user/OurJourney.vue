<template>
  <div class="our-journey-page">
    <!-- Loading 弹窗 -->
    <PageLoading :visible="loading" text="Loading..." />

    <div class="header-section">
      <div class="container">
        <h1>The Chilli Trail Journey</h1>
        <p>From the spice routes of the world to your kitchen, this is how it all began!</p>
      </div>
    </div>

    <div class="timeline-section">
      <div class="container">
        <div class="timeline-wrapper">
          <div class="timeline-line"></div>

          <div 
            v-for="(item, index) in journeys" 
            :key="item.id" 
            class="timeline-item"
          >
            <div class="timeline-dot"></div>
            <div class="timeline-content">
              <div class="content-text">
                <div class="time-label">{{ item.year }}</div>
                <h3 class="item-title">{{ item.title }}</h3>
                <p class="item-desc">{{ item.description }}</p>
              </div>
              <div class="content-image">
                <img :src="getImageUrl(item.image, '/placeholder-journey.jpg')" :alt="item.title" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <Footer />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { getImageUrl } from '@/utils/image'
import { getPublicJourneys } from '@/api/journey'
import Footer from '@/components/Footer.vue'
import PageLoading from '@/components/PageLoading.vue'

const journeys = ref([])
const loading = ref(true)

const fetchJourneys = async () => {
  loading.value = true
  try {
    const { data } = await getPublicJourneys()
    journeys.value = data.data || []
  } catch (error) {
    console.error('Fetch journey error:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchJourneys()
})
</script>

<style scoped lang="scss">
.our-journey-page {
  min-height: 100vh;
  background-color: $journey-color; // Light beige background
}

.header-section {
  text-align: center;
  padding: 60px 20px;
  
  h1 {
    font-family: 'Courier New', serif;
    font-size: 42px;
    color: #5d2b09;
    margin-bottom: 20px;
  }
  
  p {
    font-size: 18px;
    color: #8d6e63;
  }
}

.timeline-section {
  padding-bottom: 100px;
}

.container {
  max-width: 1000px;
  margin: 0 auto;
  padding: 0 20px;
}
@media (max-width: 768px) {
  .container {
    max-width: 100%;
  }
}
.timeline-wrapper {
  position: relative;
  padding: 20px 0;
}

.timeline-line {
  position: absolute;
  left: 50%;
  top: 0;
  bottom: 0;
  width: 2px;
  background-color: var(--primary-color);
  transform: translateX(-50%);
  
  @media (max-width: 768px) {
    left: 20px;
  }
}

.timeline-item {
  position: relative;
  margin-bottom: 80px;
  width: 100%;
  display: flex;
  justify-content: center;
  
  .timeline-dot {
    position: absolute;
    left: 50%;
    top: 20px;
    width: 16px;
    height: 16px;
    background-color: #5d2b09;
    border-radius: 50%;
    transform: translateX(-50%);
    z-index: 2;
    border: 3px solid #FAF3E0;
    
    @media (max-width: 768px) {
      left: 20px;
    }
  }
  
  .timeline-content {
    display: flex;
    width: 100%;
    justify-content: space-between;
    align-items: flex-start;
    gap: 60px;
    
    @media (max-width: 768px) {
      flex-direction: column;
      padding-left: 50px;
      gap: 20px;
    }
    
    .content-text {
      flex: 1;
      text-align: right;
      padding-top: 10px;
      
      @media (max-width: 768px) {
        text-align: left;
      }
      
      .time-label {
        font-weight: bold;
        color: var(--primary-color);
        font-size: 18px;
        margin-bottom: 10px;
      }
      
      .item-title {
        font-size: 24px;
        font-weight: bold;
        color: #5d2b09;
        margin-bottom: 16px;
      }
      
      .item-desc {
        font-size: 15px;
        line-height: 1.6;
        color: #5d4037;
      }
    }
    
    .content-image {
      flex: 1;
      
      img {
        width: 100%;
        max-width: 400px;
        border-radius: 12px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        transition: transform 0.3s;
        
        &:hover {
          transform: scale(1.02);
        }
      }
    }
  }
  
  // Alternating layout for desktop if desired, but user said Left Text Right Image consistently.
  // Wait, if I want consistent Left-Right layout relative to the page, but centered on line?
  // If the line splits them, Text Left / Image Right is fine.
  // But usually timeline items sit on ONE side or alternate.
  // The user said "Left side displays time title description, Right side displays image".
  // This implies the layout is TWO COLUMNS. Text Left, Image Right. And maybe the line is in the middle? 
  // OR the line is somewhere else?
  // If line is in the middle, then Left Column (Text) | Line | Right Column (Image).
  // This is what I implemented.
}
</style>
