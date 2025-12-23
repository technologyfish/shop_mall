import request from '@/utils/request'

// ==================== 认证 ====================
export function adminLogin(data) {
  return request({
    url: '/api/admin/login',
    method: 'post',
    data
  })
}

export function getAdminInfo() {
  return request({
    url: '/api/admin/me',
    method: 'get'
  })
}

export function adminLogout() {
  return request({
    url: '/api/admin/logout',
    method: 'post'
  })
}

// ==================== 用户管理 ====================
export function getUsers(params) {
  return request({
    url: '/api/admin/users',
    method: 'get',
    params
  })
}

export function getUserDetail(id) {
  return request({
    url: `/api/admin/users/${id}`,
    method: 'get'
  })
}

export function updateUserStatus(id, data) {
  return request({
    url: `/api/admin/users/${id}/status`,
    method: 'put',
    data
  })
}

// ==================== 商品管理 ====================
export function getAdminProducts(params) {
  return request({
    url: '/api/admin/products',
    method: 'get',
    params
  })
}

export function getAdminProduct(id) {
  return request({
    url: `/api/admin/products/${id}`,
    method: 'get'
  })
}

export function addProduct(data) {
  return request({
    url: '/api/admin/products',
    method: 'post',
    data
  })
}

export function updateProduct(id, data) {
  return request({
    url: `/api/admin/products/${id}`,
    method: 'put',
    data
  })
}

export function deleteProduct(id) {
  return request({
    url: `/api/admin/products/${id}`,
    method: 'delete'
  })
}

export function updateProductStatus(id, data) {
  return request({
    url: `/api/admin/products/${id}/status`,
    method: 'put',
    data
  })
}

// ==================== 订单管理 ====================
export function getAdminOrders(params) {
  return request({
    url: '/api/admin/orders',
    method: 'get',
    params
  })
}

export function getAdminOrder(id) {
  return request({
    url: `/api/admin/orders/${id}`,
    method: 'get'
  })
}

export function shipOrder(id, data = {}) {
  return request({
    url: `/api/admin/orders/${id}/ship`,
    method: 'put',
    data
  })
}

export function getStatistics() {
  return request({
    url: '/api/admin/statistics',
    method: 'get'
  })
}

// ==================== 文件上传 ====================
export function uploadFile(formData) {
  return request({
    url: '/api/admin/upload',
    method: 'post',
    data: formData,
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })
}


