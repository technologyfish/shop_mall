<template>
  <div class="my-subscriptions-page">
    <h2 class="page-title">My Subscriptions</h2>

    <!-- 管理订阅按钮 - 跳转到 Stripe Customer Portal -->
<!--    <div class="portal-section" v-if="subscriptions.length">-->
<!--      <div class="portal-info">-->
<!--        <p>Update payment method, view invoices and more in the secure Stripe portal.</p>-->
<!--      </div>-->
<!--      <el-button -->
<!--        type="primary" -->
<!--        size="large"-->
<!--        :loading="portalLoading"-->
<!--        @click="openCustomerPortal"-->
<!--        class="portal-btn"-->
<!--      >-->
<!--        <span>🔗 Manage Payment & Invoices</span>-->
<!--      </el-button>-->
<!--    </div>-->

    <div class="subscriptions-list" v-if="subscriptions.length">
      <div v-for="subscription in subscriptions" :key="subscription.id" class="subscription-card">
        <div class="card-header">
          <div class="plan-info">
            <h3>{{ subscription.plan_name }}</h3>
            <el-tag :type="getStatusType(subscription.status)" size="large">
              {{ getStatusText(subscription.status) }}
            </el-tag>
          </div>
          <div class="price-info">
            <span class="price">${{ subscription.price }}</span>
            <span class="period">/{{ getPeriodText(subscription.plan_type) }}</span>
          </div>
        </div>

        <div class="card-body">
          <div class="info-row">
            <span class="label">Bottles per delivery:</span>
            <span class="value">{{ subscription.bottles_per_delivery }}</span>
          </div>
          <div class="info-row">
            <span class="label">Current period:</span>
            <span class="value">
              {{ formatDate(subscription.current_period_start) }} - {{ formatDate(subscription.current_period_end) }}
            </span>
          </div>
        </div>

        <div class="card-footer">
          <el-button 
            v-if="subscription.status === 'active'" 
            type="danger" 
            @click="handleCancel(subscription.id)"
          >
            Cancel Subscription
          </el-button>
        </div>
      </div>
    </div>

    <el-empty v-else description="No subscriptions yet">
      <el-button type="primary" @click="goToSubscribe">Subscribe Now</el-button>
    </el-empty>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessageBox } from 'element-plus'
import message from '@/utils/message'
import { 
  getUserSubscriptions, 
  cancelSubscription,
  getCustomerPortalUrl
} from '@/api/subscription'

const router = useRouter()
const subscriptions = ref([])
const portalLoading = ref(false)

onMounted(() => {
  fetchSubscriptions()
})

const fetchSubscriptions = async () => {
  try {
    const res = await getUserSubscriptions()
    subscriptions.value = res.data.data || []
  } catch (error) {
    message.error('Failed to load subscriptions')
  }
}

// 打开 Stripe Customer Portal（用于更换信用卡、查看发票等）
const openCustomerPortal = async () => {
  portalLoading.value = true
  try {
    const res = await getCustomerPortalUrl()
    const portalUrl = res.data?.data?.portal_url
    if (portalUrl) {
      window.location.href = portalUrl
    } else {
      message.error('Failed to get portal URL')
    }
  } catch (error) {
    message.error(error.response?.data?.message || 'Failed to open subscription portal')
  } finally {
    portalLoading.value = false
  }
}

// 取消订阅（通过后端 API 调用 Stripe）
const handleCancel = async (id) => {
  try {
    await ElMessageBox.confirm(
      'Are you sure you want to cancel this subscription? This action cannot be undone.',
      'Confirm Cancellation',
      { type: 'warning', confirmButtonText: 'Yes, Cancel It' }
    )
    await cancelSubscription(id)
    message.success('Subscription cancelled successfully')
    fetchSubscriptions()
  } catch (error) {
    if (error !== 'cancel') {
      message.error(error.response?.data?.message || 'Failed to cancel subscription')
    }
  }
}

const getStatusType = (status) => {
  const types = {
    'active': 'success',
    'paused': 'warning',
    'cancelled': 'info',
    'past_due': 'danger'
  }
  return types[status] || 'info'
}

const getStatusText = (status) => {
  const texts = {
    'active': 'Active',
    'paused': 'Paused',
    'cancelled': 'Cancelled',
    'past_due': 'Past Due'
  }
  return texts[status] || status
}

const getPeriodText = (type) => {
  const periods = {
    'monthly': 'month',
    'quarterly': 'quarter',
    'yearly': 'year'
  }
  return periods[type] || 'month'
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const goToSubscribe = () => {
  router.push('/subscription')
}
</script>

<style scoped lang="scss">
.my-subscriptions-page {
  .page-title {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 30px;
    color: #333;
  }

  .portal-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    padding: 30px;
    margin-bottom: 30px;
    text-align: center;
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);

    .portal-info {
      margin-bottom: 20px;

      p {
        color: rgba(255, 255, 255, 0.9);
        font-size: 15px;
        line-height: 1.6;
        margin: 0;
      }
    }

    .portal-btn {
      padding: 16px 40px;
      font-size: 16px;
      font-weight: 600;
      border-radius: 50px;
      background: white;
      color: #667eea;
      border: none;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      transition: all 0.3s ease;

      &:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
      }

      span {
        display: flex;
        align-items: center;
        gap: 8px;
      }
    }
  }

  .subscriptions-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
  }

  .subscription-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);

    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-bottom: 20px;
      border-bottom: 1px solid #f0f0f0;
      margin-bottom: 20px;

      .plan-info {
        display: flex;
        align-items: center;
        gap: 15px;

        h3 {
          font-size: 20px;
          font-weight: bold;
          margin: 0;
          color: #333;
        }
      }

      .price-info {
        text-align: right;

        .price {
          font-size: 28px;
          font-weight: bold;
          color: #FF5722;
        }

        .period {
          font-size: 14px;
          color: #666;
        }
      }
    }

    .card-body {
      margin-bottom: 20px;

      .info-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        font-size: 15px;

        .label {
          color: #666;
        }

        .value {
          color: #333;
          font-weight: 500;
        }
      }
    }

    .card-footer {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }
  }

  // 移动端样式
  @media (max-width: 768px) {
    padding: 20px;

    .page-title {
      font-size: 20px;
      margin-bottom: 20px;
    }

    .portal-section {
      padding: 25px 20px;
      margin-bottom: 20px;

      .portal-info p {
        font-size: 14px;
      }

      .portal-btn {
        width: 100%;
        padding: 14px 30px;
        font-size: 15px;
      }
    }

    .subscriptions-list {
      gap: 15px;
    }

    .subscription-card {
      padding: 20px;

      .card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;

        .plan-info {
          width: 100%;

          h3 {
            font-size: 18px;
          }
        }

        .price-info {
          text-align: left;

          .price {
            font-size: 24px;
          }
        }
      }

      .card-body {
        .info-row {
          flex-direction: column;
          align-items: flex-start;
          gap: 5px;
          padding: 8px 0;

          .label {
            font-size: 13px;
          }

          .value {
            font-size: 14px;
          }
        }
      }

      .card-footer {
        .el-button {
          flex: 1;
          min-width: unset;
          font-size: 14px;
        }
      }
    }

    :deep(.el-empty) {
      padding: 40px 20px;
    }
  }
}
</style>




