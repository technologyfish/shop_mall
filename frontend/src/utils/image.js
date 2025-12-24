/**
 * 图片 URL 处理工具
 * 统一使用 VITE_BASE_URL 环境变量
 */

// API 基础地址 - 统一使用 VITE_BASE_URL
const baseUrl = import.meta.env.VITE_BASE_URL || import.meta.env.VITE_API_BASE_URL || ''

/**
 * 获取完整图片 URL
 * @param {string} path - 图片路径（相对路径或完整 URL）
 * @param {string} placeholder - 占位图（可选）
 * @returns {string} 完整的图片 URL
 */
export const getImageUrl = (path, placeholder = '') => {
  if (!path) return placeholder
  if (path.startsWith('http')) return path
  if (path.startsWith('data:')) return path // base64 图片
  // 确保路径以 / 开头
  const normalizedPath = path.startsWith('/') ? path : `/${path}`
  return `${baseUrl}${normalizedPath}`
}

/**
 * 获取商品图片（优先使用 images 数组的第一张）
 * 支持商品对象、订单项对象
 * @param {object} item - 商品对象或订单项对象
 * @returns {string} 图片 URL
 */
export const getProductImage = (item) => {
  if (!item) return '/placeholder-product.jpg'
  
  let imagePath = ''
  
  // 优先检查 images 数组（商品对象）
  if (item.images && Array.isArray(item.images) && item.images.length > 0) {
    imagePath = item.images[0]
  } 
  // 其次检查 image 字段（商品对象）
  else if (item.image) {
    imagePath = item.image
  }
  // 订单项：检查 product 关联的图片
  else if (item.product?.image) {
    imagePath = item.product.image
  }
  // 订单项：检查 product_image 字段
  else if (item.product_image) {
    imagePath = item.product_image
  }
  
  return imagePath ? getImageUrl(imagePath) : '/placeholder-product.jpg'
}

/**
 * 获取商品图片列表（返回完整 URL 数组）
 * @param {object} product - 商品对象
 * @returns {string[]} 图片 URL 数组
 */
export const getProductImages = (product) => {
  if (!product) return ['/placeholder-product.jpg']
  
  if (product.images && Array.isArray(product.images) && product.images.length > 0) {
    return product.images.map(img => getImageUrl(img))
  } else if (product.image) {
    return [getImageUrl(product.image)]
  }
  
  return ['/placeholder-product.jpg']
}

export default {
  getImageUrl,
  getProductImage,
  getProductImages
}
