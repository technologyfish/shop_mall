<template>
  <div class="my-subscriptions-page">
    <h2 class="page-title">My Subscriptions</h2>

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
            Cancel
          </el-button>
<!--          <el-button @click="viewDetails(subscription.id)">-->
<!--            View Details-->
<!--          </el-button>-->
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
import { ArrowLeft } from '@element-plus/icons-vue'
import message from '@/utils/message'
import { 
  getUserSubscriptions, 
  cancelSubscription
} from '@/api/subscription'

const router = useRouter()
const subscriptions = ref([])

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

const handleCancel = async (id) => {
  try {
    await ElMessageBox.confirm(
      'Are you sure you want to cancel this subscription? This action cannot be undone.',
      'Confirm Cancellation',
      { type: 'warning', confirmButtonText: 'Yes, Cancel It' }
    )
    await cancelSubscription(id)
    // message.success('Subscription cancelled successfully')
    fetchSubscriptions()
  } catch (error) {
    if (error !== 'cancel') {
      message.error(error.response?.data?.message || 'Failed to cancel subscription')
    }
  }
}

const viewDetails = (id) => {
  router.push(`/user-center/subscriptions/${id}`)
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




