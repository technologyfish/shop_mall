import request from '@/utils/request'

// 获取促销活动列表
export function getPromotions(params) {
  return request({
    url: '/api/admin/promotions',
    method: 'get',
    params
  })
}

// 获取促销活动详情
export function getPromotion(id) {
  return request({
    url: `/api/admin/promotions/${id}`,
    method: 'get'
  })
}

// 创建促销活动
export function createPromotion(data) {
  return request({
    url: '/api/admin/promotions',
    method: 'post',
    data
  })
}

// 更新促销活动
export function updatePromotion(id, data) {
  return request({
    url: `/api/admin/promotions/${id}`,
    method: 'put',
    data
  })
}

// 删除促销活动
export function deletePromotion(id) {
  return request({
    url: `/api/admin/promotions/${id}`,
    method: 'delete'
  })
}

// 更新促销活动状态
export function updatePromotionStatus(id, status) {
  return request({
    url: `/api/admin/promotions/${id}/status`,
    method: 'put',
    data: { status }
  })
}




