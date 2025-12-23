import request from '@/utils/request'

// ========== 订阅计划管理 ==========

// 获取订阅计划列表
export function getSubscriptionPlans(params) {
  return request({
    url: '/api/admin/subscription-plans',
    method: 'get',
    params
  })
}

// 获取订阅计划详情
export function getSubscriptionPlan(id) {
  return request({
    url: `/api/admin/subscription-plans/${id}`,
    method: 'get'
  })
}

// 创建订阅计划
export function createSubscriptionPlan(data) {
  return request({
    url: '/api/admin/subscription-plans',
    method: 'post',
    data
  })
}

// 更新订阅计划
export function updateSubscriptionPlan(id, data) {
  return request({
    url: `/api/admin/subscription-plans/${id}`,
    method: 'put',
    data
  })
}

// 删除订阅计划
export function deleteSubscriptionPlan(id) {
  return request({
    url: `/api/admin/subscription-plans/${id}`,
    method: 'delete'
  })
}

// ========== 订阅管理 ==========

// 获取订阅列表
export function getSubscriptions(params) {
  return request({
    url: '/api/admin/subscriptions',
    method: 'get',
    params
  })
}

// 获取订阅详情
export function getSubscription(id) {
  return request({
    url: `/api/admin/subscriptions/${id}`,
    method: 'get'
  })
}


