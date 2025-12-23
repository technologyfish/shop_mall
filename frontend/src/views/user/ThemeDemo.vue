<template>
  <div class="theme-demo-page">
    <div class="container-theme">
      <h1 class="text-primary mb-lg">主题色演示页面</h1>
      
      <!-- 颜色展示 -->
      <section class="demo-section">
        <h2 class="section-title">配色方案（锦绣华章主题）</h2>
        <div class="color-palette">
          <div class="color-item">
            <div class="color-block" style="background: var(--primary-color);"></div>
            <div class="color-info">
              <div class="color-name">胭脂红</div>
              <div class="color-code">#B8162A</div>
              <div class="color-var">--primary-color</div>
            </div>
          </div>
          <div class="color-item">
            <div class="color-block" style="background: var(--secondary-color);"></div>
            <div class="color-info">
              <div class="color-name">流金黄</div>
              <div class="color-code">#FDD972</div>
              <div class="color-var">--secondary-color</div>
            </div>
          </div>
          <div class="color-item">
            <div class="color-block" style="background: var(--accent-color);"></div>
            <div class="color-info">
              <div class="color-name">翠微绿</div>
              <div class="color-code">#0F4738</div>
              <div class="color-var">--accent-color</div>
            </div>
          </div>
        </div>
      </section>

      <!-- 按钮演示 -->
      <section class="demo-section">
        <h2 class="section-title">按钮组件</h2>
        <div class="button-grid">
          <button class="btn-theme">主题按钮</button>
          <button class="btn-theme-outline">边框按钮</button>
          <button class="btn-theme" disabled>禁用按钮</button>
          
          <!-- Element Plus按钮 -->
          <el-button type="primary">Element Primary</el-button>
          <el-button type="success">Success</el-button>
          <el-button type="warning">Warning</el-button>
        </div>
      </section>

      <!-- 卡片演示 -->
      <section class="demo-section">
        <h2 class="section-title">卡片组件</h2>
        <div class="grid-responsive">
          <div class="card-theme">
            <h3 class="text-primary">卡片标题</h3>
            <p>这是一个使用主题样式的卡片组件，支持悬停效果。</p>
            <a href="#" class="link-theme">了解更多 →</a>
          </div>
          <div class="card-theme">
            <h3 class="text-primary">响应式设计</h3>
            <p>在移动端会自动适配为单列布局。</p>
            <span class="badge-theme">NEW</span>
          </div>
          <div class="card-theme">
            <h3 class="text-primary">工具类支持</h3>
            <p>直接使用预定义的class即可应用样式。</p>
            <button class="btn-theme mobile-full-width">按钮</button>
          </div>
        </div>
      </section>

      <!-- 文字样式 -->
      <section class="demo-section">
        <h2 class="section-title">文字样式</h2>
        <p class="text-primary">主题色文字 (.text-primary)</p>
        <p class="text-secondary">辅助色文字 (.text-secondary)</p>
        <p class="text-accent">强调色文字 (.text-accent)</p>
        <p class="text-light">浅色文字 (.text-light)</p>
        <p class="text-lighter">更浅文字 (.text-lighter)</p>
        <a href="#" class="link-theme">主题链接 (.link-theme)</a>
      </section>

      <!-- 表单元素 -->
      <section class="demo-section">
        <h2 class="section-title">表单元素</h2>
        <el-form :model="form" label-width="120px">
          <el-form-item label="输入框">
            <el-input v-model="form.name" placeholder="请输入内容" />
          </el-form-item>
          <el-form-item label="单选框">
            <el-radio-group v-model="form.radio">
              <el-radio label="选项1" />
              <el-radio label="选项2" />
            </el-radio-group>
          </el-form-item>
          <el-form-item label="复选框">
            <el-checkbox-group v-model="form.checkbox">
              <el-checkbox label="选项1" />
              <el-checkbox label="选项2" />
            </el-checkbox-group>
          </el-form-item>
          <el-form-item label="开关">
            <el-switch v-model="form.switch" />
          </el-form-item>
        </el-form>
      </section>

      <!-- 移动端测试 -->
      <section class="demo-section">
        <h2 class="section-title">响应式测试</h2>
        <div class="responsive-test">
          <div class="hide-mobile">
            <div class="alert info">
              <strong>桌面端可见</strong> - 在移动端会隐藏此内容
            </div>
          </div>
          <div class="hide-desktop">
            <div class="alert success">
              <strong>移动端可见</strong> - 在桌面端会隐藏此内容
            </div>
          </div>
          <div class="mobile-full-width">
            <button class="btn-theme">移动端全宽按钮</button>
          </div>
        </div>
      </section>

      <!-- 工具类列表 -->
      <section class="demo-section">
        <h2 class="section-title">可用工具类</h2>
        <div class="card-theme">
          <h4 class="mb-md">颜色类</h4>
          <p><code>.text-primary</code> <code>.bg-primary</code> <code>.border-primary</code></p>
          
          <h4 class="mb-md mt-lg">按钮类</h4>
          <p><code>.btn-theme</code> <code>.btn-theme-outline</code></p>
          
          <h4 class="mb-md mt-lg">布局类</h4>
          <p><code>.container-theme</code> <code>.grid-responsive</code> <code>.card-theme</code></p>
          
          <h4 class="mb-md mt-lg">响应式类</h4>
          <p><code>.hide-mobile</code> <code>.hide-desktop</code> <code>.mobile-full-width</code> <code>.mobile-center</code></p>
          
          <h4 class="mb-md mt-lg">间距类</h4>
          <p><code>.mt-lg</code> <code>.mb-lg</code> <code>.mt-md</code> <code>.mb-md</code></p>
        </div>
      </section>

    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue'

const form = reactive({
  name: '',
  radio: '选项1',
  checkbox: ['选项1'],
  switch: true
})
</script>

<style scoped lang="scss">
.theme-demo-page {
  padding: 40px 0;
  min-height: 100vh;
  background: var(--bg-page);
  
  @media (max-width: 768px) {
    padding: 20px 0;
  }
}

.demo-section {
  background: white;
  border-radius: 12px;
  padding: 30px;
  margin-bottom: 30px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.08);
  
  @media (max-width: 768px) {
    padding: 20px 15px;
    margin-bottom: 20px;
  }
}

.section-title {
  font-size: 24px;
  color: var(--text-color);
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 2px solid var(--primary-color);
  
  @media (max-width: 768px) {
    font-size: 20px;
  }
}

.color-palette {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 20px;
  
  @media (max-width: 768px) {
    grid-template-columns: 1fr;
  }
}

.color-item {
  text-align: center;
}

.color-block {
  height: 120px;
  border-radius: 12px;
  margin-bottom: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.color-info {
  .color-name {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 4px;
  }
  
  .color-code {
    font-family: monospace;
    color: var(--text-light);
    font-size: 14px;
  }
  
  .color-var {
    font-family: monospace;
    color: var(--text-lighter);
    font-size: 12px;
    margin-top: 4px;
  }
}

.button-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 15px;
  
  @media (max-width: 768px) {
    grid-template-columns: 1fr;
  }
}

.responsive-test {
  .alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 15px;
    
    &.info {
      background: #e6f7ff;
      border: 1px solid #91d5ff;
      color: #0050b3;
    }
    
    &.success {
      background: #f6ffed;
      border: 1px solid #b7eb8f;
      color: #389e0d;
    }
  }
}

code {
  background: #f5f5f5;
  padding: 2px 8px;
  border-radius: 4px;
  font-family: monospace;
  font-size: 13px;
  color: var(--primary-color);
  margin: 0 4px;
}

p {
  line-height: 1.8;
  margin-bottom: 12px;
  
  &:last-child {
    margin-bottom: 0;
  }
}
</style>

