import request from '@/utils/request'

export function uploadFile(formData) {
  return request({
    url: '/api/upload',
    method: 'post',
    data: formData,
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })
}






