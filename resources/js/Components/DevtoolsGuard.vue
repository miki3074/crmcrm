<script setup>

import { onMounted } from "vue";

onMounted(() => {
    // запуск скрытой защиты
    import('@/utils/devtoolsRuntime').then(module => module.startGuard());
});

</script>

<template></template>
