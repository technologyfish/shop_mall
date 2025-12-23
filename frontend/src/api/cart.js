import request from '@/utils/request'

// 获取购物车列表
export function getCart() {
  return request({
    url: '/api/cart',
    method: 'get'
  })
}

// 添加到购物车
export function addToCart(data) {
  return request({
    url: '/api/cart',
    method: 'post',
    data
  })
}

// 更新购物车
export function updateCart(id, data) {
  return request({
    url: `/api/cart/${id}`,
    method: 'put',
    data
  })
}

// 删除购物车商品
export function deleteCart(id) {
  return request({
    url: `/api/cart/${id}`,
    method: 'delete'
  })
}

// 批量删除选中的商品
export function deleteSelectedCarts(ids) {
  return request({
    url: '/api/cart/delete-selected',
    method: 'post',
    data: { ids }
  })
}

// 切换选中状态
export function toggleSelectCart(id, selected) {
  return request({
    url: `/api/cart/${id}/select`,
    method: 'put',
    data: { selected }
  })
}

// 全选/取消全选
export function selectAllCarts(selected) {
  return request({
    url: '/api/cart/select-all',
    method: 'put',
    data: { selected }
  })
}

// 清空购物车
export function clearCart() {
  return request({
    url: '/api/cart',
    method: 'delete'
  })
}


