import request from '@/utils/request'

// 获取分类列表
export function getRecipeCategories(params) {
  return request({
    url: '/api/admin/recipe-categories',
    method: 'get',
    params
  })
}

// 获取分类详情
export function getRecipeCategory(id) {
  return request({
    url: `/api/admin/recipe-categories/${id}`,
    method: 'get'
  })
}

// 创建分类
export function createRecipeCategory(data) {
  return request({
    url: '/api/admin/recipe-categories',
    method: 'post',
    data
  })
}

// 更新分类
export function updateRecipeCategory(id, data) {
  return request({
    url: `/api/admin/recipe-categories/${id}`,
    method: 'put',
    data
  })
}

// 删除分类
export function deleteRecipeCategory(id) {
  return request({
    url: `/api/admin/recipe-categories/${id}`,
    method: 'delete'
  })
}





