<template>
  <div class="addresses-page">
    <div class="page-header-row">
      <el-button type="primary" @click="handleAdd" class="btn-add">
        <el-icon><Plus /></el-icon>
        Add New Address
      </el-button>
    </div>

      <div class="address-grid">
        <div v-for="address in addresses" :key="address.id" class="address-card">
          <div class="card-header">
            <div class="name-section">
              <h3>{{ address.name }}</h3>
              <el-tag v-if="address.is_default" type="success" size="small">Default</el-tag>
            </div>
            <div class="actions">
              <el-button link type="primary" @click="handleEdit(address)">
                <el-icon><Edit /></el-icon>
              </el-button>
              <el-button link type="danger" @click="handleDelete(address.id)">
                <el-icon><Delete /></el-icon>
              </el-button>
            </div>
          </div>
          
          <div class="card-body">
            <div class="info-row">
              <span class="label">Phone:</span>
              <span class="value">{{ address.phone }}</span>
            </div>
            <div class="info-row" v-if="address.email">
              <span class="label">Email:</span>
              <span class="value">{{ address.email }}</span>
            </div>
            <div class="info-row">
              <span class="label">Address:</span>
              <span class="value">{{ address.address }}</span>
            </div>
            <div class="info-row" v-if="address.city || address.postal_code">
              <span class="label">City/Postal:</span>
              <span class="value">
                <span v-if="address.city">{{ address.city }}</span>
                <span v-if="address.postal_code"> {{ address.postal_code }}</span>
              </span>
            </div>
          </div>
          
          <div class="card-footer" v-if="!address.is_default">
            <el-button link @click="handleSetDefault(address.id)">
              Set as Default
            </el-button>
          </div>
        </div>
      </div>

      <el-empty v-if="!addresses.length" description="No addresses yet. Add one to get started!" />

      <!-- 添加/编辑地址对话框 -->
      <el-dialog v-model="dialogVisible" :title="isEdit ? 'Edit Address' : 'Add New Address'" width="500px">
        <el-form :model="form" :rules="rules" ref="formRef" label-width="140px">
          <el-form-item label="Full Name" prop="name">
            <el-input v-model="form.name" placeholder="Full Name" />
          </el-form-item>
          <el-form-item label="Email" prop="email">
            <el-input v-model="form.email" placeholder="Email" type="email" />
          </el-form-item>
          <el-form-item label="Phone" prop="phone">
            <el-input v-model="form.phone" placeholder="Phone" />
          </el-form-item>
          <el-form-item label="Shipping Address" prop="address">
            <el-input v-model="form.address" type="textarea" :rows="3" placeholder="Shipping Address" />
          </el-form-item>
          <el-form-item label="City" prop="city">
            <el-input v-model="form.city" placeholder="City" />
          </el-form-item>
          <el-form-item label="Postal Code" prop="postal_code">
            <el-input v-model="form.postal_code" placeholder="Postal Code" />
          </el-form-item>
          <el-form-item label="Set as Default">
            <el-switch v-model="form.is_default" />
          </el-form-item>
        </el-form>
        <template #footer>
          <el-button @click="dialogVisible = false">Cancel</el-button>
          <el-button type="primary" @click="handleSubmit" :loading="loading">Save</el-button>
        </template>
      </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessageBox } from 'element-plus'
import message from '@/utils/message'
import { Plus, Edit, Delete, ArrowLeft } from '@element-plus/icons-vue'
import { getAddresses, addAddress, updateAddress, deleteAddress, setDefaultAddress } from '@/api/address'

const addresses = ref([])
const dialogVisible = ref(false)
const isEdit = ref(false)
const loading = ref(false)
const formRef = ref()

const form = reactive({
  id: null,
  name: '',
  email: '',
  phone: '',
  address: '',
  city: '',
  postal_code: '',
  is_default: false
})

const rules = {
  name: [{ required: true, message: 'Please enter Full Name', trigger: 'blur' }],
  email: [
    { required: true, message: 'Please enter Email', trigger: 'blur' },
    { type: 'email', message: 'Invalid email format', trigger: 'blur' }
  ],
  phone: [{ required: true, message: 'Please enter Phone', trigger: 'blur' }],
  address: [{ required: true, message: 'Please enter Shipping Address', trigger: 'blur' }],
  city: [{ required: true, message: 'Please enter City', trigger: 'blur' }],
  postal_code: [{ required: true, message: 'Please enter Postal Code', trigger: 'blur' }]
}

onMounted(() => {
  fetchAddresses()
})

const fetchAddresses = async () => {
  try {
    const res = await getAddresses()
    addresses.value = res.data.data || []
  } catch (error) {
    console.error(error)
  }
}

const handleAdd = () => {
  isEdit.value = false
  resetForm()
  dialogVisible.value = true
}

const handleEdit = (address) => {
  isEdit.value = true
  Object.assign(form, address)
  dialogVisible.value = true
}

const handleSubmit = async () => {
  await formRef.value.validate(async (valid) => {
    if (valid) {
      loading.value = true
      try {
        if (isEdit.value) {
          await updateAddress(form.id, form)
          message.success('Address updated successfully')
        } else {
          await addAddress(form)
          message.success('Address added successfully')
        }
        dialogVisible.value = false
        fetchAddresses()
      } catch (error) {
        message.error(error.message || 'Failed to save address')
      } finally {
        loading.value = false
      }
    }
  })
}

const handleSetDefault = async (id) => {
  try {
    await setDefaultAddress(id)
    message.success('Default address updated')
    fetchAddresses()
  } catch (error) {
    message.error(error.message || 'Failed to set default address')
  }
}

const handleDelete = async (id) => {
  try {
    await ElMessageBox.confirm('Are you sure you want to delete this address?', 'Confirm', {
      confirmButtonText: 'Delete',
      cancelButtonText: 'Cancel',
      type: 'warning'
    })
    await deleteAddress(id)
    message.success('Address deleted successfully')
    fetchAddresses()
  } catch (error) {
    if (error !== 'cancel') {
      message.error(error.message || 'Failed to delete address')
    }
  }
}

const resetForm = () => {
  Object.assign(form, {
    id: null,
    name: '',
    email: '',
    phone: '',
    address: '',
    city: '',
    postal_code: '',
    is_default: false
  })
}
</script>

<style scoped lang="scss">
.addresses-page {
  .page-header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    
    .page-title {
      margin: 0;
      font-size: 24px;
      font-weight: bold;
    }
    
    .btn-add {
      background-color: var(--primary-color);
      border: none;
      
      &:hover {
        background-color: var(--primary-dark);
      }
    }
  }

  .address-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
  }

  .address-card {
    background: #fff;
    border: 1px solid #e8e8e8;
    border-radius: 12px;
    padding: 20px;
    transition: all 0.3s ease;
    
    &:hover {
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      border-color: var(--primary-color);
    }

    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 15px;
      padding-bottom: 15px;
      border-bottom: 1px solid #f0f0f0;

      .name-section {
        display: flex;
        align-items: center;
        gap: 10px;
        
        h3 {
          margin: 0;
          font-size: 18px;
          font-weight: bold;
          color: #333;
        }
      }

      .actions {
        display: flex;
        gap: 5px;
      }
    }

    .card-body {
      margin-bottom: 15px;

      .info-row {
        display: flex;
        margin-bottom: 10px;
        line-height: 1.6;

        .label {
          font-weight: 500;
          color: #666;
          min-width: 70px;
          flex-shrink: 0;
        }

        .value {
          color: #333;
          flex: 1;
        }
      }
    }

    .card-footer {
      padding-top: 10px;
      border-top: 1px solid #f0f0f0;
    }
  }

  @media (max-width: 768px) {
    padding: 20px;

    .address-grid {
      grid-template-columns: 1fr;
      gap: 15px;
    }
    
    .page-header-row {
      flex-direction: column;
      align-items: stretch;
      gap: 15px;
      margin-bottom: 20px;
      
      h2 {
        font-size: 20px;
      }
      
      .btn-add {
        width: 100%;
      }
    }

    .address-card {
      padding: 15px;

      .card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;

        .address-name {
          font-size: 16px;
        }
      }

      .card-body {
        font-size: 14px;
        line-height: 1.8;
      }

      .card-actions {
        flex-wrap: wrap;

        .el-button {
          flex: 1;
          min-width: 100px;
        }
      }
    }
  }
}
</style>


