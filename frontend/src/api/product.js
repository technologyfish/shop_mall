import request from '@/utils/request'

// 获取商品列表
export function getProducts(params) {
  return request({
    url: '/api/products',
    method: 'get',
    params
  })
}

// 获取商品详情
export function getProduct(id) {
  return request({
    url: `/api/products/${id}`,
    method: 'get'
  })
}

// 获取分类列表
export function getCategories() {
  return request({
    url: '/api/categories',
    method: 'get'
  })
}


