<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps(['companies', 'userId', 'isAdmin'])
const emit = defineEmits(['refresh'])

// Состояния для модальных окон
const showDeleteModal = ref(false)
const showAllMyCompaniesModal = ref(false)
const showAllOtherCompaniesModal = ref(false)

const deletePassword = ref('')
const selectedCompanyId = ref(null)

// Фильтрация
const myCompanies = computed(() => props.companies.filter(c => String(c.user_id) === String(props.userId)))
const otherCompanies = computed(() => props.companies.filter(c => String(c.user_id) !== String(props.userId)))

// Логика удаления
const openDelete = (id) => {
    selectedCompanyId.value = id
    showDeleteModal.value = true
}

const confirmDelete = async () => {
    try {
        await axios.delete(`/api/companies/${selectedCompanyId.value}`, { data: { password: deletePassword.value } })
        showDeleteModal.value = false
        deletePassword.value = ''
        emit('refresh')
    } catch (e) {
        alert(e.response?.data?.message || 'Ошибка пароля')
    }
}
</script>

<template>
    <div class="grid grid-cols-1 lg:grid-cols-[7fr_3fr] gap-8 items-start">

        <div class="dashboard-grid">
            <!-- Карточка КОМПАНИИ -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-building" style="margin-right: 8px; color:#4361ee;"></i> Активные компании</h3>
                    <i class="fas fa-ellipsis-h"></i>
                </div>
                <div class="company-list">
                    <div v-for="company in myCompanies" :key="company.id"
                         @click="router.visit(`/companies/${company.id}`)"
                         class="company-item" style="cursor: pointer">

                        <button @click.stop="openDelete(company.id)" class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 p-1 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-md transition z-10">✕</button>

                        <div class="mb-3">
                            <img v-if="company.logo" :src="`/storage/${company.logo}`" class="w-16 h-16 rounded-xl object-cover border dark:border-slate-600" />
                            <div v-else class="w-16 h-16 rounded-xl bg-white dark:bg-slate-700 grid place-items-center text-3xl shadow-sm">🏢</div>
                        </div>

                        <div class="company-info">
                            <h4 class="">{{ company.name }}</h4>
                            <p class="">{{ company.projects?.length || 0 }} проектов</p>
                        </div>
                    </div>


                </div>
                <div style="margin-top: 16px; text-align: center;">
                    <span style="color: #4361ee; font-weight: 500; cursor: pointer;">+ Все компании (24) <i class="fas fa-arrow-right"></i></span>
                </div>

                <button
                    v-if="myCompanies.length > 1"
                    @click="showAllMyCompaniesModal = true"
                    style="margin-top: 16px; text-align: center;"
                >
                     <span style="color: #4361ee; font-weight: 500; cursor: pointer;">Все компании ({{ myCompanies.length }})</span>
                </button>

            </div>
        </div>




<!--        <section class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm h-full flex flex-col">-->
<!--            <div class="flex justify-between items-center mb-6">-->
<!--                <h3 class="flex items-center gap-2 text-lg font-bold text-slate-700 dark:text-slate-200">-->
<!--                    <span class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">🤝</span> Другие компании-->
<!--                </h3>-->
<!--                &lt;!&ndash; Кнопка "Все" (появляется если > 3) &ndash;&gt;-->
<!--                <button-->
<!--                    v-if="otherCompanies.length > 3"-->
<!--                    @click="showAllOtherCompaniesModal = true"-->
<!--                    class="text-sm font-bold text-emerald-600 hover:text-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 px-3 py-1.5 rounded-lg transition"-->
<!--                >-->
<!--                    Все ({{ otherCompanies.length }})-->
<!--                </button>-->
<!--            </div>-->

<!--            &lt;!&ndash; Вертикальный список &ndash;&gt;-->
<!--            &lt;!&ndash; h-[450px] для синхронизации высоты с левым блоком &ndash;&gt;-->
<!--            <div class="overflow-y-auto h-[450px] pr-2 custom-scrollbar flex flex-col gap-4">-->
<!--                <div v-for="company in otherCompanies" :key="company.id"-->
<!--                     @click="router.visit(`/companies/${company.id}`)"-->
<!--                     class="flex-shrink-0 p-5 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-2xl hover:shadow-md hover:border-emerald-400 dark:hover:border-emerald-500 transition cursor-pointer flex items-center gap-4">-->
<!--                    <img v-if="company.logo" :src="`/storage/${company.logo}`" class="w-14 h-14 rounded-xl object-cover bg-white" />-->
<!--                    <div v-else class="w-14 h-14 rounded-xl bg-white dark:bg-slate-700 grid place-items-center text-xl shadow-sm">🏢</div>-->

<!--                    <div>-->
<!--                        <p class="font-bold text-lg text-slate-800 dark:text-slate-200 truncate">{{ company.name }}</p>-->

<!--                    </div>-->
<!--                </div>-->
<!--            </div>-->
<!--        </section>-->
<!--   -->
    </div>

    <!-- МОДАЛКА: Все мои компании -->
    <div v-if="showAllMyCompaniesModal" class="fixed inset-0 z-[90] grid place-items-center bg-slate-950/60 backdrop-blur-sm p-4" @click.self="showAllMyCompaniesModal = false">
        <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl w-full max-w-5xl max-h-[90vh] flex flex-col border dark:border-slate-800 shadow-2xl">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-slate-800 dark:text-white">Все мои компании</h3>
                <button @click="showAllMyCompaniesModal = false" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full transition">✕</button>
            </div>
            <div class="overflow-y-auto p-2">
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    <div v-for="company in myCompanies" :key="company.id"
                         @click="router.visit(`/companies/${company.id}`)"
                         class="group relative h-[200px] flex flex-col items-center justify-center p-4 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl hover:border-indigo-500 cursor-pointer">
                        <div class="mb-3">
                            <img v-if="company.logo" :src="`/storage/${company.logo}`" class="w-16 h-16 rounded-xl object-cover" />
                            <div v-else class="w-16 h-16 rounded-xl bg-white dark:bg-slate-700 grid place-items-center text-3xl">🏢</div>
                        </div>
                        <p class="font-bold text-center text-slate-800 dark:text-white truncate w-full">{{ company.name }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- МОДАЛКА: Все другие компании -->
    <div v-if="showAllOtherCompaniesModal" class="fixed inset-0 z-[90] grid place-items-center bg-slate-950/60 backdrop-blur-sm p-4" @click.self="showAllOtherCompaniesModal = false">
        <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl w-full max-w-4xl max-h-[90vh] flex flex-col border dark:border-slate-800 shadow-2xl">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-slate-800 dark:text-white">Все другие компании</h3>
                <button @click="showAllOtherCompaniesModal = false" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full transition">✕</button>
            </div>
            <div class="overflow-y-auto p-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="company in otherCompanies" :key="company.id"
                     @click="router.visit(`/companies/${company.id}`)"
                     class="flex items-center gap-4 p-4 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl hover:border-emerald-500 cursor-pointer">
                    <img v-if="company.logo" :src="`/storage/${company.logo}`" class="w-12 h-12 rounded-xl object-cover" />
                    <div v-else class="w-12 h-12 rounded-xl bg-white dark:bg-slate-700 grid place-items-center text-xl">🏢</div>
                    <p class="font-bold text-slate-800 dark:text-white">{{ company.name }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Модалка удаления (Без изменений) -->
    <div v-if="showDeleteModal" class="fixed inset-0 z-[100] grid place-items-center bg-slate-950/60 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl w-full max-w-sm border dark:border-slate-800 shadow-2xl animate-in fade-in zoom-in duration-200">
            <h4 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Подтверждение</h4>
            <p class="text-sm text-slate-500 mb-4">Для удаления введите ваш пароль от аккаунта.</p>
            <input v-model="deletePassword" type="password" class="w-full px-4 py-3 rounded-xl border dark:bg-slate-800 dark:border-slate-700 mb-4 outline-none focus:ring-2 focus:ring-rose-500" placeholder="Ваш пароль" />
            <div class="flex gap-2">
                <button @click="showDeleteModal = false" class="flex-1 py-3 text-sm font-bold text-slate-500">Отмена</button>
                <button @click="confirmDelete" class="flex-1 py-3 text-sm font-bold bg-rose-600 text-white rounded-xl shadow-lg shadow-rose-600/20">Удалить</button>
            </div>
        </div>
    </div>
</template>

<style scoped>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
}

body {
    background: #f4f6fb;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    padding: 20px;
}

/* главный контейнер — дашборд */
.dashboard {
    max-width: 1440px;
    width: 100%;
    background: #ffffff;
    border-radius: 32px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

/* шапка */
.header {
    padding: 24px 32px;
    background: white;
    border-bottom: 1px solid #eef2f6;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
}

.logo-area {
    display: flex;
    align-items: center;
    gap: 12px;
}

.logo-icon {
    background: linear-gradient(135deg, #4361ee, #9c4dff);
    width: 44px;
    height: 44px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    box-shadow: 0 6px 12px rgba(67, 97, 238, 0.2);
}

.logo-text h2 {
    font-size: 20px;
    font-weight: 600;
    color: #1e293b;
    letter-spacing: -0.3px;
}

.logo-text span {
    font-size: 13px;
    color: #64748b;
    font-weight: 400;
}

.search-bar {
    background: #f8fafd;
    padding: 10px 20px;
    border-radius: 60px;
    display: flex;
    align-items: center;
    gap: 10px;
    border: 1px solid #e2e8f0;
    width: 300px;
    transition: 0.2s;
}

.search-bar:focus-within {
    border-color: #4361ee;
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
}

.search-bar i {
    color: #94a3b8;
    font-size: 16px;
}

.search-bar input {
    border: none;
    background: transparent;
    outline: none;
    width: 100%;
    font-size: 15px;
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 20px;
}

.notification-badge {
    position: relative;
    font-size: 22px;
    color: #475569;
    cursor: pointer;
}

.notification-badge::after {
    content: '';
    position: absolute;
    top: 2px;
    right: 4px;
    width: 8px;
    height: 8px;
    background: #ef4444;
    border-radius: 50%;
    border: 2px solid white;
}

.avatar {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f1f4f9;
    padding: 6px 12px 6px 6px;
    border-radius: 40px;
    cursor: pointer;
    transition: 0.2s;
}

.avatar:hover {
    background: #e6eaf0;
}

.avatar-img {
    width: 38px;
    height: 38px;
    background: linear-gradient(145deg, #4361ee, #6c5ce7);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 16px;
}

.avatar-name {
    font-weight: 500;
    color: #1e293b;
    font-size: 14px;
}

/* основная навигация (табы) */
.tabs {
    display: flex;
    gap: 8px;
    padding: 0 32px;
    background: white;
    border-bottom: 1px solid #eef2f6;
}

.tab {
    padding: 16px 20px;
    font-weight: 500;
    color: #64748b;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    transition: 0.15s;
    font-size: 15px;
}

.tab i {
    margin-right: 8px;
    font-size: 15px;
}

.tab.active {
    color: #4361ee;
    border-bottom-color: #4361ee;
    font-weight: 600;
}

/* основное содержимое */
.content {
    padding: 32px;
    background: #f9fbfe;
}

/* карточки приветствия и статистики */
.welcome-row {
    display: flex;
    gap: 24px;
    flex-wrap: wrap;
    margin-bottom: 32px;
}

.greeting-card {
    background: linear-gradient(145deg, #ffffff, #f9f9ff);
    flex: 2;
    min-width: 280px;
    border-radius: 24px;
    padding: 28px 30px;
    box-shadow: 0 8px 20px -8px rgba(0, 27, 65, 0.1);
    border: 1px solid #ffffff80;
}

.greeting-card h2 {
    font-size: 26px;
    font-weight: 600;
    color: #0b1b33;
}

.greeting-card h2 span {
    color: #4361ee;
}

.greeting-card p {
    margin-top: 8px;
    color: #475569;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 15px;
}

.stats-cards {
    flex: 3;
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.stat-item {
    background: white;
    border-radius: 24px;
    padding: 20px 22px;
    flex: 1 1 140px;
    box-shadow: 0 5px 15px -8px #b0b9ce;
    border: 1px solid #eef2f6;
    transition: 0.15s;
}

.stat-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 25px -12px #a0abc0;
}

.stat-title {
    color: #64748b;
    font-size: 14px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.stat-number {
    font-size: 32px;
    font-weight: 700;
    color: #1e293b;
}

.stat-change {
    margin-top: 8px;
    font-size: 13px;
    color: #10b981;
    background: #d1fae5;
    display: inline-block;
    padding: 4px 10px;
    border-radius: 30px;
}

/* секции с карточками компаний, проектов, задач */
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
    gap: 24px;
    margin-top: 20px;
}

.card {
    background: white;
    border-radius: 26px;
    padding: 24px;
    box-shadow: 0 8px 24px -10px #cdd8e6;
    border: 1px solid #eaedf2;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 22px;
}

.card-header h3 {
    font-weight: 600;
    font-size: 18px;
    color: #1e293b;
}

.card-header i {
    color: #94a3b8;
    background: #f1f5f9;
    padding: 8px;
    border-radius: 50%;
    transition: 0.15s;
    cursor: pointer;
}

.card-header i:hover {
    background: #e0e7ff;
    color: #4361ee;
}

/* списки компаний / проектов */
.company-item, .project-item, .task-item {
    display: flex;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f0f3f7;
}

.company-item:last-child, .project-item:last-child, .task-item:last-child {
    border-bottom: none;
}

.company-avatar {
    width: 42px;
    height: 42px;
    background: #ecf2ff;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: #4361ee;
    margin-right: 14px;
}

.company-info h4, .project-info h4 {
    font-size: 16px;
    font-weight: 600;
    color: #0f182a;
}

.company-info p, .project-info p {
    font-size: 13px;
    color: #5f6c84;
    margin-top: 2px;
}

.company-meta, .project-meta {
    margin-left: auto;
    font-size: 13px;
    background: #f1f5f9;
    padding: 4px 12px;
    border-radius: 40px;
    color: #334155;
}

/* проекты — особый стиль */
.project-progress {
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 12px;
}

.progress-bar {
    width: 70px;
    height: 6px;
    background: #e4e9f2;
    border-radius: 10px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    width: 65%;
    background: #4361ee;
    border-radius: 10px;
}

.task-item {
    display: flex;
    align-items: center;
}

.task-check {
    margin-right: 15px;
    color: #cbd5e1;
    cursor: pointer;
    transition: 0.1s;
}

.task-check:hover {
    color: #4361ee;
}

.task-check.completed {
    color: #10b981;
}

.task-content {
    flex: 1;
}

.task-title {
    font-weight: 500;
    color: #1e293b;
}

.task-deadline {
    font-size: 12px;
    color: #8b9bb5;
    display: flex;
    align-items: center;
    gap: 4px;
    margin-top: 4px;
}

.task-priority {
    font-size: 11px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 50px;
    background: #fee2e2;
    color: #b91c1c;
}

.priority-high {
    background: #fee2e2;
    color: #b91c1c;
}

.priority-medium {
    background: #fef9c3;
    color: #854d0e;
}

.priority-low {
    background: #dcfce7;
    color: #166534;
}

.task-more {
    margin-left: 10px;
    color: #94a3b8;
    cursor: pointer;
}

/* быстрые действия */
.quick-actions {
    margin-top: 32px;
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    background: white;
    padding: 18px 28px;
    border-radius: 40px;
    border: 1px solid #eef2f6;
}

.actions-left {
    display: flex;
    gap: 15px;
}

.action-btn {
    background: #f0f4fe;
    border: none;
    padding: 10px 22px;
    border-radius: 40px;
    font-weight: 500;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: 0.15s;
    border: 1px solid transparent;
}

.action-btn i {
    color: #4361ee;
}

.action-btn:hover {
    background: #e6edfd;
    border-color: #cddfff;
}

.date-badge {
    color: #475569;
    background: #f1f5f9;
    padding: 8px 22px;
    border-radius: 40px;
    font-size: 14px;
    font-weight: 500;
}

/* адаптивность */
@media (max-width: 850px) {
    .header {
        flex-direction: column;
        align-items: stretch;
    }
    .search-bar {
        width: 100%;
    }
    .tabs {
        overflow-x: auto;
        padding: 0 16px;
    }
}

/* Простая анимация плавного появления */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
