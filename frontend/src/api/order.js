import request from '@/utils/request'

// 获取订单列表
export function getOrders(params) {
  return request({
    url: '/api/orders',
    method: 'get',
    params
  })
}

// 获取订单详情
export function getOrder(id) {
  return request({
    url: `/api/orders/${id}`,
    method: 'get'
  })
}

// 创建订单
export function createOrder(data) {
  return request({
    url: '/api/orders',
    method: 'post',
    data
  })
}

// 取消订单
export function cancelOrder(id) {
  return request({
    url: `/api/orders/${id}/cancel`,
    method: 'put'
  })
}


