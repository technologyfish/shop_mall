import request from '@/utils/request'

// 创建支付
export function createPayment(data) {
  return request({
    url: '/api/payments/create',
    method: 'post',
    data
  })
}

// 查询支付状态
export function getPaymentStatus(orderNo, params = {}) {
  return request({
    url: `/api/payments/${orderNo}/status`,
    method: 'get',
    params
  })
}

// 支付回调（一般由第三方支付平台调用后端）
// 前端不直接调用此接口


