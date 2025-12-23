<template>
  <div class="recipe-detail-page" v-if="recipe">
    <div class="recipe-header">
      <div class="container">
        <h1 class="recipe-title">{{ recipe.title }}</h1>
        <p class="recipe-subtitle">{{ recipe.subtitle }}</p>
        <div class="recipe-meta">
          <span v-if="recipe.cook_time">⏱ {{ recipe.cook_time }} mins</span>
          <span v-if="recipe.servings">👥 {{ recipe.servings }} servings</span>
          <span class="difficulty" :class="recipe.difficulty">{{ recipe.difficulty }}</span>
        </div>
      </div>
    </div>

    <div class="recipe-content">
      <div class="container">
        <div class="recipe-image">
          <img :src="getImageUrl(recipe.image, '/placeholder-recipe.jpg')" :alt="recipe.title" />
        </div>

        <div class="recipe-section">
          <h2>Ingredients</h2>
          <div class="ingredients" v-html="recipe.ingredients"></div>
        </div>

        <div class="recipe-section">
          <h2>Instructions</h2>
          <div class="instructions" v-html="recipe.instructions"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { getImageUrl } from '@/utils/image'
import { getRecipe } from '@/api/recipe'

const route = useRoute()
const recipe = ref(null)

const fetchRecipe = async () => {
  try {
    const res = await getRecipe(route.params.id)
    recipe.value = res.data.data || res.data
  } catch (error) {
    console.error('Failed to load recipe:', error)
  }
}

onMounted(() => {
  fetchRecipe()
})
</script>

<style lang="scss" scoped>
.recipe-detail-page {
  min-height: 100vh;
  background: #fafafa;
}

.recipe-header {
  background: white;
  padding: 60px 0 40px;
  border-bottom: 1px solid #e5e5e5;
  
  .container {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 20px;
    text-align: center;
  }
  
  .recipe-title {
    font-size: 42px;
    font-weight: bold;
    color: #333;
    margin-bottom: 16px;
  }
  
  .recipe-subtitle {
    font-size: 18px;
    color: #666;
    margin-bottom: 20px;
  }
  
  .recipe-meta {
    display: flex;
    gap: 20px;
    justify-content: center;
    font-size: 16px;
    color: #999;
    
    .difficulty {
      padding: 4px 12px;
      border-radius: 4px;
      background: #f0f0f0;
      
      &.easy { background: #d4edda; color: #155724; }
      &.medium { background: #fff3cd; color: #856404; }
      &.hard { background: #f8d7da; color: #721c24; }
    }
  }
}

.recipe-content {
  padding: 60px 0;
  
  .container {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 20px;
  }
  
  .recipe-image {
    width: 100%;
    height: 500px;
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 60px;
    
    img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
  }
  
  .recipe-section {
    background: white;
    padding: 40px;
    border-radius: 12px;
    margin-bottom: 30px;
    
    h2 {
      font-size: 28px;
      font-weight: bold;
      color: #333;
      margin-bottom: 24px;
      padding-bottom: 16px;
      border-bottom: 2px solid #f0f0f0;
    }
    
    .ingredients,
    .instructions {
      font-size: 16px;
      line-height: 1.8;
      color: #333;
      
      ::v-deep(ul),
      ::v-deep(ol) {
        padding-left: 20px;
        
        li {
          margin-bottom: 12px;
        }
      }
    }
  }
}

@media (max-width: 768px) {
  .recipe-header {
    padding: 40px 0 30px;
    
    .recipe-title {
      font-size: 28px;
    }
    
    .recipe-subtitle {
      font-size: 16px;
    }
    
    .recipe-meta {
      flex-direction: column;
      gap: 10px;
      font-size: 14px;
    }
  }
  
  .recipe-content {
    padding: 40px 0;
    
    .recipe-image {
      height: 300px;
      margin-bottom: 40px;
    }
    
    .recipe-section {
      padding: 24px;
      
      h2 {
        font-size: 24px;
      }
    }
  }
}
</style>




