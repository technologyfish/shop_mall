import request from '@/utils/request'

// 获取订阅计划列表
export function getSubscriptionPlans() {
  return request({
    url: '/api/subscription-plans',
    method: 'get'
  })
}

// 创建订阅
export function createSubscription(data) {
  return request({
    url: '/api/subscriptions/create',
    method: 'post',
    data
  })
}

// 验证订阅支付（支付成功后调用）
export function verifySubscription(sessionId) {
  return request({
    url: '/api/subscriptions/verify',
    method: 'post',
    data: { session_id: sessionId }
  })
}

// 获取用户订阅列表
export function getUserSubscriptions() {
  return request({
    url: '/api/subscriptions',
    method: 'get'
  })
}

// 获取订阅详情
export function getSubscriptionDetail(id) {
  return request({
    url: `/api/subscriptions/${id}`,
    method: 'get'
  })
}

// 取消订阅
export function cancelSubscription(id) {
  return request({
    url: `/api/subscriptions/${id}/cancel`,
    method: 'post'
  })
}

// 暂停订阅
export function pauseSubscription(id) {
  return request({
    url: `/api/subscriptions/${id}/pause`,
    method: 'post'
  })
}

// 恢复订阅
export function resumeSubscription(id) {
  return request({
    url: `/api/subscriptions/${id}/resume`,
    method: 'post'
  })
}

// 获取 Stripe Customer Portal URL
// 用户可以在 Portal 中管理订阅、更换信用卡、查看账单等
export function getCustomerPortalUrl() {
  return request({
    url: '/api/subscriptions/portal',
    method: 'post'
  })
}




