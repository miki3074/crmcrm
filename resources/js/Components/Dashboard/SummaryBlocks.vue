<script setup>
import { computed } from 'vue'

const props = defineProps({
  summary: { type: Object, default: () => ({}) },
  loading: Boolean,
})

const managingByCompany = computed(() => {
  return (props.summary.managing_projects || []).reduce((acc, p) => {
    const companyName = p.company?.name || 'Без компании'
    ;(acc[companyName] ||= []).push(p)
    return acc
  }, {})
})

const allTasksByCompanyAndProject = computed(() => {
  return (props.summary.all_tasks || []).reduce((acc, t) => {
    const companyName = t.project?.company?.name || 'Без компании'
    const projectName = t.project?.name || 'Без проекта'
    acc[companyName] ??= {}
    acc[companyName][projectName] ??= []
    if (!acc[companyName][projectName].some(task => task.id === t.id)) {
      acc[companyName][projectName].push(t)
    }
    return acc
  }, {})
})

const allSubtasksByCompany = computed(() => {
  return (props.summary.all_subtasks || []).reduce((acc, st) => {
    const companyName = st.task?.project?.company?.name || 'Без компании'
    const projectName = st.task?.project?.name || 'Без проекта'
    acc[companyName] ??= {}
    acc[companyName][projectName] ??= []
    if (!acc[companyName][projectName].some(s => s.id === st.id)) {
      acc[companyName][projectName].push(st)
    }
    return acc
  }, {})
})
</script>

<template>
  <section>
    <h3 class="text-lg font-semibold mb-2">Сводка</h3>
    <div v-if="loading">Загрузка...</div>
    <div v-else class="grid md:grid-cols-2 gap-6">
      <!-- Я руковожу -->
      <div>
        <h4 class="font-medium mb-2">Проекты, которыми я руковожу</h4>
        <div v-for="(items, company) in managingByCompany" :key="company" class="mb-3">
          <div class="text-sm mb-1 text-slate-500">{{ company }}</div>
          <ul class="list-disc list-inside">
            <li v-for="p in items" :key="p.id">{{ p.name }}</li>
          </ul>
        </div>
      </div>

      <!-- Задачи по компаниям/проектам -->
      <div>
        <h4 class="font-medium mb-2">Все задачи</h4>
        <div v-for="(byProject, company) in allTasksByCompanyAndProject" :key="company" class="mb-3">
          <div class="text-sm mb-1 text-slate-500">{{ company }}</div>
          <div v-for="(tasks, project) in byProject" :key="project" class="mb-2">
            <div class="text-xs mb-1">{{ project }}</div>
            <ul class="list-disc list-inside">
              <li v-for="t in tasks" :key="t.id">{{ t.title }}</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Подзадачи -->
      <div class="md:col-span-2">
        <h4 class="font-medium mb-2">Все подзадачи</h4>
        <div class="grid md:grid-cols-2 gap-4">
          <div v-for="(byProject, company) in allSubtasksByCompany" :key="company" class="border rounded p-3">
            <div class="font-medium mb-1">{{ company }}</div>
            <div v-for="(subs, project) in byProject" :key="project" class="mb-2">
              <div class="text-xs mb-1">{{ project }}</div>
              <ul class="list-disc list-inside">
                <li v-for="s in subs" :key="s.id">{{ s.title }}</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>
</template>
