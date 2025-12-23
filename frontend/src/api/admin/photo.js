import request from '@/utils/request'

// 获取照片列表
export function getPhotos(params) {
  return request({
    url: '/api/admin/photos',
    method: 'get',
    params
  })
}

// 获取照片详情
export function getPhoto(id) {
  return request({
    url: `/api/admin/photos/${id}`,
    method: 'get'
  })
}

// 添加照片
export function addPhoto(data) {
  return request({
    url: '/api/admin/photos',
    method: 'post',
    data
  })
}

// 更新照片
export function updatePhoto(id, data) {
  return request({
    url: `/api/admin/photos/${id}`,
    method: 'put',
    data
  })
}

// 删除照片
export function deletePhoto(id) {
  return request({
    url: `/api/admin/photos/${id}`,
    method: 'delete'
  })
}




