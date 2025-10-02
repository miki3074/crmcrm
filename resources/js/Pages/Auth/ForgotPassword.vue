<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';

const email = ref('');
const notification = ref(null);

const submitTelegram = async () => {
    notification.value = null;
    try {
        const res = await axios.post('/api/password/telegram', {
            email: email.value,
        });

        // если success = true
        notification.value = { type: 'success', text: res.data.message };
    } catch (error) {
        if (error.response && error.response.data) {
            // Laravel вернул JSON с ошибкой
            notification.value = { type: 'error', text: error.response.data.message };
        } else {
            notification.value = { type: 'error', text: '❌ Не удалось отправить запрос' };
        }
    }
};
</script>



<template>
    <GuestLayout>
        <Head title="Forgot Password" />

        <!-- Уведомления -->
        <transition name="fade">
            <div v-if="notification" class="mb-4 p-4 rounded-lg"
                 :class="notification.type === 'success'
                          ? 'bg-green-100 text-green-800 border border-green-300'
                          : 'bg-red-100 text-red-800 border border-red-300'">
                {{ notification.text }}
            </div>
        </transition>

       <form @submit.prevent="submitTelegram">
    <div>
        <InputLabel for="email" value="Email" />
        <TextInput
            id="email"
            type="email"
            class="mt-1 block w-full"
            v-model="email"
            required
            autofocus
        />
    </div>

    <div class="flex items-center justify-end mt-4">
        <PrimaryButton>
            🤖 Сбросить через Telegram
        </PrimaryButton>
    </div>
</form>

    </GuestLayout>
</template>

<style>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.5s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
