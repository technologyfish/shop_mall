import { createRouter, createWebHistory } from 'vue-router'
import { useUserStore } from '@/store/user'
import { useAdminStore } from '@/store/admin'

const routes = [
  // 根路径重定向
  {
    path: '/',
    redirect: '/home'
  },
  // 用户端路由
  {
    path: '/',
    component: () => import('@/layouts/UserLayout.vue'),
    children: [
      {
        path: 'home',
        name: 'Home',
        component: () => import('@/views/user/Home.vue')
      },
      {
        path: 'shop',
        name: 'Shop',
        component: () => import('@/views/user/Products.vue')
      },
      {
        path: 'products',
        name: 'Products',
        component: () => import('@/views/user/Products.vue')
      },
      {
        path: 'product/:id',
        name: 'ProductDetail',
        component: () => import('@/views/user/ProductDetail.vue')
      },
      {
        path: 'our-journey',
        name: 'OurJourney',
        component: () => import('@/views/user/OurJourney.vue')
      },
      {
        path: 'recipes',
        name: 'Recipes',
        component: () => import('@/views/user/Recipes.vue')
      },
      {
        path: 'recipe/:id',
        name: 'RecipeDetail',
        component: () => import('@/views/user/RecipeDetail.vue')
      },
      {
        path: 'photos',
        name: 'Photos',
        component: () => import('@/views/user/Photos.vue')
      },
      {
        path: 'contact',
        name: 'Contact',
        component: () => import('@/views/user/Contact.vue')
      },
      {
        path: 'article/:id',
        name: 'ArticleDetail',
        component: () => import('@/views/user/ArticleDetail.vue')
      },
      {
        path: 'subscription',
        name: 'Subscription',
        component: () => import('@/views/user/Subscription.vue')
      },
      {
        path: 'subscription/success',
        name: 'SubscriptionSuccess',
        component: () => import('@/views/user/SubscriptionSuccess.vue')
      },
      {
        path: 'subscription/cancelled',
        name: 'SubscriptionCancelled',
        component: () => import('@/views/user/SubscriptionCancelled.vue')
      },
      {
        path: 'cart',
        name: 'Cart',
        component: () => import('@/views/user/Cart.vue'),
        meta: { requiresAuth: true }
      },
      {
        path: 'orders/create',
        name: 'OrderCreate',
        component: () => import('@/views/user/OrderCreate.vue'),
        meta: { requiresAuth: true }
      },
      {
        path: 'payment/:id',
        name: 'Payment',
        component: () => import('@/views/user/Payment.vue'),
        meta: { requiresAuth: true }
      },
      {
        path: 'payment/result',
        name: 'PaymentResult',
        component: () => import('@/views/user/PaymentResult.vue'),
        meta: { requiresAuth: true }
      },
      {
        path: 'user-center',
        component: () => import('@/layouts/UserCenterLayout.vue'),
        meta: { requiresAuth: true },
        children: [
          {
            path: '',
            name: 'UserCenterHome',
            component: () => import('@/views/user/UserCenterHome.vue')
          },
          {
            path: 'orders',
            name: 'UserOrders',
            component: () => import('@/views/user/Orders.vue')
          },
          {
            path: 'orders/:id',
            name: 'UserOrderDetail',
            component: () => import('@/views/user/OrderDetail.vue')
          },
          {
            path: 'profile',
            name: 'UserProfile',
            component: () => import('@/views/user/Profile.vue')
          },
          {
            path: 'addresses',
            name: 'UserAddresses',
            component: () => import('@/views/user/Addresses.vue')
          },
          {
            path: 'subscriptions',
            name: 'UserSubscriptions',
            component: () => import('@/views/user/MySubscriptions.vue')
          },
          {
            path: 'sign-out',
            name: 'UserSignOut',
            component: () => import('@/views/user/SignOut.vue')
          }
        ]
      }
    ]
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/user/Login.vue')
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('@/views/user/Register.vue')
  },
  {
    path: '/forgot-password',
    name: 'ForgotPassword',
    component: () => import('@/views/user/ForgotPassword.vue')
  },

  // 管理后台路由
  {
    path: '/admin',
    component: () => import('@/layouts/AdminLayout.vue'),
    meta: { requiresAdmin: true },
    redirect: '/admin/announcements',
    children: [
      {
        path: '',
        redirect: '/admin/announcements'
      },
      {
        path: 'announcements',
        name: 'AdminAnnouncements',
        component: () => import('@/views/admin/Announcements.vue')
      },
      {
        path: 'banners',
        name: 'AdminBanners',
        component: () => import('@/views/admin/Banners.vue')
      },
      {
        path: 'products',
        name: 'AdminProducts',
        component: () => import('@/views/admin/Products.vue')
      },
      {
        path: 'recipe-categories',
        name: 'AdminRecipeCategories',
        component: () => import('@/views/admin/RecipeCategories.vue')
      },
      {
        path: 'recipes',
        name: 'AdminRecipes',
        component: () => import('@/views/admin/Recipes.vue')
      },
      {
        path: 'photos',
        name: 'AdminPhotos',
        component: () => import('@/views/admin/Photos.vue')
      },
      {
        path: 'subscription-plans',
        name: 'AdminSubscriptionPlans',
        component: () => import('@/views/admin/SubscriptionPlans.vue')
      },
          {
            path: 'subscriptions',
            name: 'AdminSubscriptions',
            component: () => import('@/views/admin/Subscriptions.vue')
          },
          {
            path: 'subscription-orders',
            name: 'AdminSubscriptionOrders',
            component: () => import('@/views/admin/SubscriptionOrders.vue')
          },
      {
        path: 'articles',
        name: 'AdminArticles',
        component: () => import('@/views/admin/Articles.vue')
      },
      {
        path: 'journeys',
        name: 'AdminJourneys',
        component: () => import('@/views/admin/Journeys.vue')
      },
      {
        path: 'orders',
        name: 'AdminOrders',
        component: () => import('@/views/admin/Orders.vue')
      },
      {
        path: 'orders/:id',
        name: 'AdminOrderDetail',
        component: () => import('@/views/admin/OrderDetail.vue')
      },
      {
        path: 'users',
        name: 'AdminUsers',
        component: () => import('@/views/admin/Users.vue')
      },
      {
        path: 'contact-info',
        name: 'AdminContactInfo',
        component: () => import('@/views/admin/ContactInfo.vue')
      },
      {
        path: 'contact-forms',
        name: 'AdminContactForms',
        component: () => import('@/views/admin/ContactForms.vue')
      },
      {
        path: 'mail-transfer-forms',
        name: 'AdminMailTransferForms',
        component: () => import('@/views/admin/MailTransferForms.vue')
      },
      {
        path: 'promotions',
        name: 'AdminPromotions',
        component: () => import('@/views/admin/Promotions.vue')
      },
      {
        path: 'shipping-settings',
        name: 'AdminShippingSettings',
        component: () => import('@/views/admin/ShippingSettings.vue')
      },
      {
        path: 'email-tasks',
        name: 'AdminEmailTasks',
        component: () => import('@/views/admin/EmailTasks.vue')
      }
    ]
  },
  {
    path: '/admin/login',
    name: 'AdminLogin',
    component: () => import('@/views/admin/Login.vue')
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// 路由守卫
router.beforeEach((to, from, next) => {
  // 管理后台认证检查
  if (to.path.startsWith('/admin') && to.path !== '/admin/login') {
    if (to.meta.requiresAdmin) {
      const adminStore = useAdminStore()
      if (!adminStore.token) {
        next('/admin/login')
        return
      }
    }
  }

  // 用户端认证检查
  if (to.meta.requiresAuth) {
    const userStore = useUserStore()
    if (!userStore.token) {
      next('/login')
      return
    }
  }

  next()
})

export default router
