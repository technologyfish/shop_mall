import request from '@/utils/request'

// 获取照片列表
export function getPhotos(params) {
  return request({
    url: '/api/photos',
    method: 'get',
    params
  })
}

// 获取首页展示的照片
export function getFeaturedPhotos(params) {
  return request({
    url: '/api/photos/featured',
    method: 'get',
    params
  })
}

// 获取照片详情
export function getPhotoDetail(id) {
  return request({
    url: `/api/photos/${id}`,
    method: 'get'
  })
}




