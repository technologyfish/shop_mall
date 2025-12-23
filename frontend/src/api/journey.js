import request from '@/utils/request'

// 获取Journey列表 (Admin)
export function getJourneys(params) {
  return request({
    url: '/api/admin/journeys',
    method: 'get',
    params
  })
}

// 获取Journey列表 (User)
export function getPublicJourneys() {
  return request({
    url: '/api/journeys',
    method: 'get'
  })
}

// 创建Journey
export function createJourney(data) {
  return request({
    url: '/api/admin/journeys',
    method: 'post',
    data
  })
}

// 更新Journey
export function updateJourney(id, data) {
  return request({
    url: `/api/admin/journeys/${id}`,
    method: 'put',
    data
  })
}

// 删除Journey
export function deleteJourney(id) {
  return request({
    url: `/api/admin/journeys/${id}`,
    method: 'delete'
  })
}






