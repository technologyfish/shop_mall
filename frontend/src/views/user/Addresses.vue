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
              <h3>{{ address.first_name }} {{ address.last_name }}</h3>
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
              <span class="label">City/Post:</span>
              <span class="value">{{ address.city }}, {{ address.postcode }}</span>
            </div>
            <div class="info-row">
              <span class="label">Address:</span>
              <span class="value">{{ address.address }}</span>
            </div>
            <div class="info-row" v-if="address.phone">
              <span class="label">Phone:</span>
              <span class="value">{{ address.phone }}</span>
            </div>
            <div class="info-row" v-if="address.email">
              <span class="label">Email:</span>
              <span class="value">{{ address.email }}</span>
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
      <el-dialog 
        v-model="dialogVisible" 
        :title="isEdit ? 'Edit Address' : 'Add New Address'" 
        width="600px"
        class="address-dialog"
      >
        <el-form :model="form" :rules="rules" ref="formRef" label-position="top" class="custom-form">
          <!-- First Name / Last Name -->
          <div class="form-row">
            <el-form-item label="First name" prop="first_name">
              <el-input v-model="form.first_name" placeholder="First name" />
            </el-form-item>
            <el-form-item label="Last name" prop="last_name">
              <el-input v-model="form.last_name" placeholder="Last name" />
            </el-form-item>
          </div>

          <!-- Email & Phone (可选) -->
          <div class="form-row">
            <el-form-item label="Email" prop="email">
              <el-input v-model="form.email" placeholder="Email" type="email" />
            </el-form-item>
            <el-form-item label="Phone (optional)" prop="phone">
              <el-input v-model="form.phone" placeholder="Phone (optional)" />
            </el-form-item>
          </div>

          <!-- City / Postcode (移到上方) -->
          <div class="form-row">
            <el-form-item label="City" prop="city">
              <el-input v-model="form.city" placeholder="City" />
            </el-form-item>
            <el-form-item label="Postcode" prop="postcode">
              <el-input v-model="form.postcode" placeholder="Postcode" />
            </el-form-item>
          </div>

          <!-- Address (移到下方) -->
          <el-form-item label="Address" prop="address">
            <el-input v-model="form.address" placeholder="Address" />
          </el-form-item>

          <el-form-item>
            <el-checkbox v-model="form.is_default" label="Set as default address" />
          </el-form-item>
        </el-form>
        <template #footer>
          <div class="dialog-footer">
            <el-button @click="dialogVisible = false">Cancel</el-button>
            <el-button type="primary" @click="handleSubmit" :loading="loading" class="btn-save">
              {{ isEdit ? 'Update Address' : 'Add Address' }}
            </el-button>
          </div>
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
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  city: '',
  postcode: '',
  address: '',
  is_default: false
})

const rules = {
  first_name: [{ required: true, message: 'Please enter First Name', trigger: 'blur' }],
  last_name: [{ required: true, message: 'Please enter Last Name', trigger: 'blur' }],
  email: [
    { required: true, message: 'Please enter Email', trigger: 'blur' },
    { type: 'email', message: 'Invalid email format', trigger: 'blur' }
  ],
  city: [{ required: true, message: 'Please enter City', trigger: 'blur' }],
  postcode: [{ required: true, message: 'Please enter Postcode', trigger: 'blur' }],
  address: [{ required: true, message: 'Please enter Address', trigger: 'blur' }]
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
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    city: '',
    postcode: '',
    address: '',
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
          min-width: 80px;
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

  /* 弹窗样式调整 */
  .address-dialog {
    :deep(.el-dialog__body) {
      padding-top: 10px;
    }
  }

  .form-row {
    display: flex;
    gap: 20px;
    
    .el-form-item {
      flex: 1;
    }
  }

  .custom-form {
    :deep(.el-form-item__label) {
      font-weight: 500;
      margin-bottom: 4px;
      color: #333;
    }

    :deep(.el-input__wrapper) {
      padding: 8px 15px;
      border-radius: 8px;
    }
  }

  .dialog-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    
    .btn-save {
      padding: 10px 25px;
      height: auto;
    }
  }

  @media (max-width: 768px) {
    padding: 20px;

    .address-grid {
      grid-template-columns: 1fr;
      gap: 15px;
    }
    
    .form-row {
      flex-direction: column;
      gap: 0;
    }

    .page-header-row {
      flex-direction: column;
      align-items: stretch;
      gap: 15px;
      margin-bottom: 20px;
      
      .btn-add {
        width: 100%;
      }
    }
  }
}
</style>


