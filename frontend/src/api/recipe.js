import request from '@/utils/request'

// 获取菜谱分类及其菜谱
export function getRecipeCategories() {
  return request({
    url: '/api/recipe-categories',
    method: 'get'
  })
}

// 获取菜谱列表
export function getRecipes(params) {
  return request({
    url: '/api/recipes',
    method: 'get',
    params
  })
}

// 获取菜谱详情
export function getRecipe(id) {
  return request({
    url: `/api/recipes/${id}`,
    method: 'get'
  })
}

// 根据slug获取菜谱
export function getRecipeBySlug(slug) {
  return request({
    url: `/api/recipes/slug/${slug}`,
    method: 'get'
  })
}
