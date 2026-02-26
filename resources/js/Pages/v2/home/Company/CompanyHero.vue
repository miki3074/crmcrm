<script setup>
import { computed } from 'vue'

const props = defineProps({
    company: Object,
    isOwner: Boolean,
    isAdmin: Boolean,
    canCreate: Boolean
})

const emit = defineEmits(['create', 'openMembers'])
</script>

<template>



        <!-- Обложка компании -->
        <div class="company-cover">
            <div class="company-title-block">
                <div class="company-logo-large">
                    <img
                        v-if="company?.logo"
                        :src="`/storage/${company.logo}`"
                        alt="Logo"
                        class="w-20 h-20 rounded-xl object-cover bg-white"
                    />
                    <div v-else class="">
                        🏢
                    </div>
                </div>
                <div class="company-name">
                    <h1>{{ company?.name || 'У вас нет задач или проектов в этой компании...' }}</h1>
                    <span >ID: {{ company?.id }}</span>
                    <p v-if="isOwner">вы: Владелец</p>

                </div>
            </div>
            <div class="company-stats-mini">
                <div class="stat-mini-item">
                    <div class="stat-mini-value">24</div>
                    <div class="stat-mini-label">сотрудника</div>
                </div>
                <div class="stat-mini-item">
                    <div class="stat-mini-value">9</div>
                    <div class="stat-mini-label">проектов</div>
                </div>
                <div class="stat-mini-item">
                    <div class="stat-mini-value">47</div>
                    <div class="stat-mini-label">задач</div>
                </div>
            </div>
        </div>

        <div class="company-info-bar">
            <div class="info-tags">
                <span class="tag-item"><i class="fas fa-globe"></i> technoprom.ru</span>
                <span class="tag-item"><i class="fas fa-envelope"></i> info@technoprom.ru</span>
                <span class="tag-item"><i class="fas fa-phone-alt"></i> +7 (495) 233-44-55</span>
                <span class="tag-item"><i class="fas fa-tag"></i> IT, Разработка</span>
            </div>
            <div class="action-buttons">
                <button class="btn-outline"><i class="fas fa-pen"></i> Редактировать</button>


                <button
                    v-if="canCreate"
                    @click="$emit('create')"
                    class="btn-primary"
                >
                    <i class="fas fa-plus-circle"></i>
                    Новый проект
                </button>

            </div>
        </div>





</template>

<style scoped>
@import "main.css";

</style>
