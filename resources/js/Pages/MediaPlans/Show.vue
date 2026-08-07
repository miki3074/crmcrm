<script setup>
import { computed, ref, watch } from 'vue'
import {
    Head,
    Link,
    router,
    useForm,
} from '@inertiajs/vue3'

const props = defineProps({
    mediaPlan: {
        type: Object,
        required: true,
    },

    cities: {
        type: Array,
        default: () => [],
    },

    availableResponsibles: {
        type: Array,
        default: () => [],
    },

    canEdit: {
    type: Boolean,
    default: false,
},
})

/*
|--------------------------------------------------------------------------
| Вспомогательные функции
|--------------------------------------------------------------------------
*/

const normalizeNumber = (value) => {
    const number = Number(value)

    return Number.isFinite(number)
        ? number
        : 0
}

const money = (value) => {
    return new Intl.NumberFormat('ru-RU', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(normalizeNumber(value))
}

const getInitials = (name) => {
    if (!name) {
        return '?'
    }

    return name
        .trim()
        .split(/\s+/)
        .map(part => part[0])
        .slice(0, 2)
        .join('')
        .toUpperCase()
}

/*
|--------------------------------------------------------------------------
| Преобразование строки медиаплана в форму
|--------------------------------------------------------------------------
*/

const mapItemToForm = (item) => ({
    id: item.id,

    format: item.format ?? '',
    materials_url: item.materials_url ?? '',

    duration_seconds:
        item.duration_seconds ?? 0,

    outputs_per_day:
        item.outputs_per_day ?? 0,

    days_count:
        item.days_count ?? 0,

    price_per_second:
        item.price_per_second ?? 0,

    /*
     * Для соцсетей / офлайна.
     */
    fixed_price:
        item.fixed_price ?? 0,

    kpi:
        item.kpi ?? '',

    /*
     * Реальные пользователи через pivot
     * media_plan_item_user.
     */
    responsible_ids:
        item.responsibles?.map(
            user => Number(user.id)
        ) ?? [],

    start_date:
        item.start_date ?? '',

    end_date:
        item.end_date ?? '',
})

/*
|--------------------------------------------------------------------------
| Основная форма
|--------------------------------------------------------------------------
*/

const form = useForm({
    name:
        props.mediaPlan.name ?? '',

    description:
        props.mediaPlan.description ?? '',

    status:
        props.mediaPlan.status ?? 'draft',

    start_date:
        props.mediaPlan.start_date ?? '',

    end_date:
        props.mediaPlan.end_date ?? '',

    items:
        (props.mediaPlan.items ?? [])
            .map(mapItemToForm),
})

/*
|--------------------------------------------------------------------------
| Метаданные строк
|--------------------------------------------------------------------------
|
| В form.items мы храним только редактируемые данные.
| Информация о станции, городе, типе активности остаётся
| в props.mediaPlan.items.
|
*/

const itemMeta = computed(() => {
    return new Map(
        (props.mediaPlan.items ?? [])
            .map(item => [
                Number(item.id),
                item,
            ])
    )
})

const getMeta = (itemId) => {
    return itemMeta.value.get(
        Number(itemId)
    )
}

/*
|--------------------------------------------------------------------------
| Расчёты
|--------------------------------------------------------------------------
*/

/*
 * Всего выходов =
 * количество выходов в день × количество дней
 */
const calculateTotalOutputs = (item) => {
    return (
        normalizeNumber(item.outputs_per_day)
        *
        normalizeNumber(item.days_count)
    )
}

/*
 * Стоимость:
 *
 * RADIO:
 * цена секунды × хронометраж × количество выходов
 *
 * SOCIAL / OFFLINE / OTHER:
 * фиксированная стоимость
 */
const calculateItemPrice = (item) => {
    const meta = getMeta(item.id)

    if (meta?.type === 'radio') {
        return (
            normalizeNumber(
                item.price_per_second
            )
            *
            normalizeNumber(
                item.duration_seconds
            )
            *
            calculateTotalOutputs(item)
        )
    }

    return normalizeNumber(
        item.fixed_price
    )
}

/*
 * Общий бюджет.
 */
const calculatedTotal = computed(() => {
    return form.items.reduce(
        (sum, item) => {
            return (
                sum
                + calculateItemPrice(item)
            )
        },
        0
    )
})

/*
 * Общее количество выходов.
 */
const totalOutputs = computed(() => {
    return form.items.reduce(
        (sum, item) => {
            return (
                sum
                + calculateTotalOutputs(item)
            )
        },
        0
    )
})

/*
|--------------------------------------------------------------------------
| Отображение строки
|--------------------------------------------------------------------------
*/

const itemTitle = (itemId) => {
    const item = getMeta(itemId)

    if (!item) {
        return 'Неизвестная активность'
    }

    if (item.type === 'radio') {
        const station =
            item.radio_station

        if (!station) {
            return 'Радиостанция'
        }

        return `${station.name}${
            station.frequency
                ? ` ${station.frequency} FM`
                : ''
        }`
    }

    return (
        item.platform_name
        || 'Активность'
    )
}

const itemSubtitle = (itemId) => {
    const item = getMeta(itemId)

    if (!item) {
        return ''
    }

    if (item.type === 'radio') {
        return item.city?.name ?? ''
    }

    const labels = {
        social:
            'Мультиплатформенная активность',

        offline:
            'Офлайн-активность',

        other:
            'Другая активность',
    }

    return labels[item.type] ?? ''
}

const itemTypeClasses = (itemId) => {
    const type =
        getMeta(itemId)?.type

    if (type === 'radio') {
        return 'border-l-indigo-500'
    }

    if (type === 'social') {
        return 'border-l-orange-400'
    }

    if (type === 'offline') {
        return 'border-l-sky-500'
    }

    return 'border-l-slate-400'
}

const itemTypeBadge = (itemId) => {
    const type =
        getMeta(itemId)?.type

    const badges = {
        radio: {
            text: 'РАДИО',
            classes:
                'bg-indigo-50 text-indigo-700',
        },

        social: {
            text: 'DIGITAL',
            classes:
                'bg-orange-50 text-orange-700',
        },

        offline: {
            text: 'ОФЛАЙН',
            classes:
                'bg-sky-50 text-sky-700',
        },

        other: {
            text: 'ДРУГОЕ',
            classes:
                'bg-slate-100 text-slate-600',
        },
    }

    return badges[type]
        ?? badges.other
}

/*
|--------------------------------------------------------------------------
| Ошибки конкретной строки
|--------------------------------------------------------------------------
*/

const itemError = (
    index,
    field
) => {
    return form.errors[
        `items.${index}.${field}`
    ]
}

/*
|--------------------------------------------------------------------------
| Сохранение медиаплана
|--------------------------------------------------------------------------
*/

const submit = () => {
    form.put(
        route(
            'media-plans.update',
            props.mediaPlan.id
        ),
        {
            preserveScroll: true,
        }
    )
}

/*
|--------------------------------------------------------------------------
| Синхронизация после Inertia reload
|--------------------------------------------------------------------------
|
| Например:
| - добавили новую строку;
| - удалили строку;
| - сервер вернул обновлённый медиаплан.
|
*/

watch(
    () => props.mediaPlan.items,
    (newItems) => {
        form.items =
            (newItems ?? [])
                .map(mapItemToForm)
    },
    {
        deep: true,
    }
)

/*
|--------------------------------------------------------------------------
| Модальное окно добавления строки
|--------------------------------------------------------------------------
*/

const isAddModalOpen =
    ref(false)

const addItemForm = useForm({
    type: 'radio',

    city_id: null,

    radio_station_id: null,

    platform_name: '',
})

/*
 * Радиостанции только выбранного города.
 */
const availableStations =
    computed(() => {
        if (!addItemForm.city_id) {
            return []
        }

        const city =
            props.cities.find(
                city =>
                    Number(city.id)
                    === Number(
                        addItemForm.city_id
                    )
            )

        return (
            city?.radio_stations
            ?? []
        )
    })

/*
 * Если поменяли город —
 * старая станция сбрасывается.
 */
watch(
    () => addItemForm.city_id,
    () => {
        const availableIds =
            availableStations.value.map(
                station =>
                    Number(station.id)
            )

        if (
            !availableIds.includes(
                Number(
                    addItemForm
                        .radio_station_id
                )
            )
        ) {
            addItemForm
                .radio_station_id = null
        }
    }
)

/*
 * Если переключили тип активности.
 */
watch(
    () => addItemForm.type,
    (type) => {
        addItemForm.clearErrors()

        if (type !== 'radio') {
            addItemForm.city_id = null
            addItemForm.radio_station_id =
                null
        }

        if (type === 'radio') {
            addItemForm.platform_name = ''
        }
    }
)

const openAddModal = () => {
    addItemForm.reset()

    addItemForm.type =
        'radio'

    addItemForm.clearErrors()

    isAddModalOpen.value =
        true
}

const closeAddModal = () => {
    if (
        addItemForm.processing
    ) {
        return
    }

    isAddModalOpen.value =
        false

    addItemForm.reset()

    addItemForm.clearErrors()
}

const submitNewItem = () => {
    addItemForm.post(
        route(
            'media-plans.items.store',
            props.mediaPlan.id
        ),
        {
            preserveScroll: true,

            onSuccess: () => {
                closeAddModal()
            },
        }
    )
}

/*
|--------------------------------------------------------------------------
| Удаление строки
|--------------------------------------------------------------------------
*/

const deleteItem = (itemId) => {
    if (
        !confirm(
            'Удалить строку медиаплана?'
        )
    ) {
        return
    }

    router.delete(
        route(
            'media-plans.items.destroy',
            [
                props.mediaPlan.id,
                itemId,
            ]
        ),
        {
            preserveScroll: true,
        }
    )
}


const deleteMediaPlan = () => {
    if (
        !confirm(
            `Удалить медиаплан «${props.mediaPlan.name}»?\n\nЭто действие нельзя отменить.`
        )
    ) {
        return
    }

    router.delete(
        route(
            'media-plans.destroy',
            props.mediaPlan.id
        ),
        {
            preserveScroll: true,
        }
    )
}

/*
|--------------------------------------------------------------------------
| Справочники
|--------------------------------------------------------------------------
*/

const statusOptions = [
    {
        value: 'draft',
        label: 'Черновик',
    },
    {
        value: 'active',
        label: 'В работе',
    },
    {
        value: 'completed',
        label: 'Завершён',
    },
    {
        value: 'cancelled',
        label: 'Отменён',
    },
]

const activityOptions = [
    {
        value: 'social',
        label: 'Социальная платформа',
    },
    {
        value: 'offline',
        label: 'Офлайн-активность',
    },
    {
        value: 'other',
        label: 'Другая активность',
    },
]
</script>

<template>
    <Head :title="mediaPlan.name" />

    <div class="min-h-screen bg-slate-50">

        <!-- ========================================================= -->
        <!-- ВЕРХНЯЯ ПАНЕЛЬ -->
        <!-- ========================================================= -->

        <div
            class="sticky top-0 z-30 bg-white/95 backdrop-blur border-b border-slate-200"
        >
            <div
                class="max-w-[1900px] mx-auto px-4 sm:px-6 py-4"
            >
                <div
                    class="flex flex-col xl:flex-row xl:items-center justify-between gap-4"
                >
                    <!-- Левая часть -->

                    <div class="min-w-0">

                        <Link
                            :href="
                                route(
                                    'klients.show',
                                    mediaPlan.klient.id
                                )
                            "
                            class="inline-flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-800"
                        >
                            <span>←</span>
                            Вернуться к клиенту
                        </Link>

                        <div
                            class="flex flex-wrap items-center gap-3 mt-2"
                        >
                            <h1
                                class="text-2xl font-bold text-slate-900 truncate"
                            >
                                {{ mediaPlan.name }}
                            </h1>

                            <span
                                class="px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 text-[10px] font-bold uppercase"
                            >
                                Медиаплан
                            </span>
                        </div>

                        <p
                            class="text-sm text-slate-500 mt-1"
                        >
                            Клиент:

                            <span
                                class="font-semibold text-slate-700"
                            >
                                {{ mediaPlan.klient.name }}
                            </span>
                        </p>
                    </div>

                    <!-- Правая часть -->

                    <div
                        class="flex flex-wrap items-center gap-3"
                    >
                        <!-- Количество строк -->

                        <div
                            class="px-4 py-2 bg-slate-100 rounded-xl"
                        >
                            <div
                                class="text-[10px] uppercase text-slate-400 font-bold"
                            >
                                Активностей
                            </div>

                            <div
                                class="text-lg font-bold text-slate-800"
                            >
                                {{ form.items.length }}
                            </div>
                        </div>

                        <!-- Всего выходов -->

                        <div
                            class="px-4 py-2 bg-slate-100 rounded-xl"
                        >
                            <div
                                class="text-[10px] uppercase text-slate-400 font-bold"
                            >
                                Выходов
                            </div>

                            <div
                                class="text-lg font-bold text-slate-800"
                            >
                                {{ totalOutputs }}
                            </div>
                        </div>

                        <!-- Бюджет -->

                        <div
                            class="px-4 py-2 bg-emerald-50 rounded-xl border border-emerald-100"
                        >
                            <div
                                class="text-[10px] uppercase text-emerald-500 font-bold"
                            >
                                Общий бюджет
                            </div>

                            <div
                                class="text-lg font-bold text-emerald-700"
                            >
                                {{ money(calculatedTotal) }} ₽
                            </div>
                        </div>

                        <button
                        v-if="canEdit"
                            type="button"
                            @click="openAddModal"
                            class="px-4 py-2.5 rounded-xl border border-indigo-200 text-indigo-700 bg-indigo-50 hover:bg-indigo-100 font-bold text-sm transition"
                        >
                            + Добавить строку
                        </button>

                        <button
                        v-if="canEdit"
                            type="button"
                            :disabled="form.processing"
                            @click="submit"
                            class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm disabled:opacity-50 transition"
                        >
                            {{
                                form.processing
                                    ? 'Сохранение...'
                                    : 'Сохранить'
                            }}
                        </button>

                        <button
    v-if="canEdit"
    type="button"
    @click="deleteMediaPlan"
    class="px-4 py-2.5 rounded-xl border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100 font-bold text-sm transition"
>
    <i class="fas fa-trash-alt mr-2"></i>
    Удалить медиаплан
</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- ОСНОВНОЙ КОНТЕНТ -->
        <!-- ========================================================= -->

        <main
            class="max-w-[2000px] mx-auto px-4 sm:px-6 py-6 space-y-6"
        >

            <!-- ===================================================== -->
            <!-- НАСТРОЙКИ МЕДИАПЛАНА -->
            <!-- ===================================================== -->

            <section
            v-if="canEdit"
                class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5"
            >
                <div
                    class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4"
                >

                    <!-- Название -->

                    <div
                    
                        class="xl:col-span-2"
                    >
                        <label
                            class="block text-xs font-bold uppercase text-slate-400"
                        >
                            Название
                        </label>

                        <input
                            v-model="form.name"
                            type="text"
                            class="mt-1 w-full rounded-xl border-slate-300"
                        >

                        <p
                            v-if="form.errors.name"
                            class="mt-1 text-xs text-rose-600"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <!-- Статус -->

                    <div>
                        <label
                            class="block text-xs font-bold uppercase text-slate-400"
                        >
                            Статус
                        </label>

                        <select
                            v-model="form.status"
                            class="mt-1 w-full rounded-xl border-slate-300"
                        >
                            <option
                                v-for="option in statusOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Начало -->

                    <div>
                        <label
                            class="block text-xs font-bold uppercase text-slate-400"
                        >
                            Начало
                        </label>

                        <input
                            v-model="form.start_date"
                            type="date"
                            class="mt-1 w-full rounded-xl border-slate-300"
                        >
                    </div>

                    <!-- Завершение -->

                    <div>
                        <label
                            class="block text-xs font-bold uppercase text-slate-400"
                        >
                            Завершение
                        </label>

                        <input
                            v-model="form.end_date"
                            type="date"
                            class="mt-1 w-full rounded-xl border-slate-300"
                        >

                        <p
                            v-if="form.errors.end_date"
                            class="mt-1 text-xs text-rose-600"
                        >
                            {{ form.errors.end_date }}
                        </p>
                    </div>

                    <!-- Описание -->

                    <div
                        class="md:col-span-2 xl:col-span-5"
                    >
                        <label
                            class="block text-xs font-bold uppercase text-slate-400"
                        >
                            Описание
                        </label>

                        <textarea
                            v-model="form.description"
                            rows="2"
                            class="mt-1 w-full rounded-xl border-slate-300"
                        ></textarea>
                    </div>
                </div>
            </section>

            <!-- ===================================================== -->
            <!-- ТАБЛИЦА -->
            <!-- ===================================================== -->

            <section
                class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden"
            >

                <div
                    v-if="form.errors.items"
                    class="px-5 py-3 bg-rose-50 border-b border-rose-100 text-rose-700 text-sm"
                >
                    {{ form.errors.items }}
                </div>

                <!-- Пустой медиаплан -->

                <div
                    v-if="!form.items.length"
                    class="py-16 text-center"
                >
                    <div
                        class="w-16 h-16 mx-auto rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 text-2xl"
                    >
                        +
                    </div>

                    <h3
                        class="mt-4 font-bold text-slate-700"
                    >
                        В медиаплане пока нет активностей
                    </h3>

                    <p
                        class="mt-1 text-sm text-slate-400"
                    >
                        Добавьте радиостанцию, digital или офлайн-активность.
                    </p>

                    <button
                        type="button"
                        @click="openAddModal"
                        class="mt-4 px-5 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-bold"
                    >
                        Добавить строку
                    </button>
                </div>

                <!-- Таблица -->

                <div
                    v-else
                    class="overflow-x-auto"
                >
                    <table
                        class="w-full min-w-[1900px] border-collapse"
                    >

                        <!-- ================================================= -->
                        <!-- HEAD -->
                        <!-- ================================================= -->

                        <thead>
                            <tr
                                class="bg-slate-100 text-left"
                            >
                                <th
                                    class="sticky left-0 z-20 bg-slate-100 w-[240px] px-3 py-3 border-r border-b border-slate-200 text-xs uppercase text-slate-500"
                                >
                                    Станция / платформа
                                </th>

                                <th
                                    class="w-[270px] px-3 py-3 border-r border-b border-slate-200 text-xs uppercase text-slate-500"
                                >
                                    Формат и материалы
                                </th>

                                <th
                                    class="w-[110px] px-3 py-3 border-r border-b border-slate-200 text-xs uppercase text-slate-500"
                                >
                                    Хронометраж
                                </th>

                                <th
                                    class="w-[110px] px-3 py-3 border-r border-b border-slate-200 text-xs uppercase text-slate-500"
                                >
                                    Выходов / день
                                </th>

                                <th
                                    class="w-[90px] px-3 py-3 border-r border-b border-slate-200 text-xs uppercase text-slate-500"
                                >
                                    Дней
                                </th>

                                <th
                                    class="w-[110px] px-3 py-3 border-r border-b border-slate-200 text-xs uppercase text-slate-500"
                                >
                                    Всего
                                </th>

                                <th
                                    class="w-[150px] px-3 py-3 border-r border-b border-slate-200 text-xs uppercase text-slate-500"
                                >
                                    Цена
                                </th>

                                <th
                                    class="w-[160px] px-3 py-3 border-r border-b border-slate-200 text-xs uppercase text-slate-500"
                                >
                                    Стоимость
                                </th>

                                <th
                                    class="w-[270px] px-3 py-3 border-r border-b border-slate-200 text-xs uppercase text-slate-500"
                                >
                                    KPI
                                </th>

                                <th
                                    class="w-[250px] px-3 py-3 border-r border-b border-slate-200 text-xs uppercase text-slate-500"
                                >
                                    Ответственные
                                </th>

                                <th
                                    class="w-[250px] px-3 py-3 border-r border-b border-slate-200 text-xs uppercase text-slate-500"
                                >
                                    Сроки
                                </th>

                                <th v-if="canEdit"
                                    class="w-[70px] px-3 py-3 border-b border-slate-200"
                                ></th>
                            </tr>
                        </thead>

                        <!-- ================================================= -->
                        <!-- BODY -->
                        <!-- ================================================= -->

                        <tbody>
                            <tr
                                v-for="(item, index) in form.items"
                                :key="item.id"
                                class="align-top border-l-4"
                                :class="
                                    itemTypeClasses(
                                        item.id
                                    )
                                "
                            >

                                <!-- ========================================= -->
                                <!-- Станция / платформа -->
                                <!-- ========================================= -->

                                <td
                                    class="sticky left-0 z-10 bg-white px-3 py-4 border-r border-b border-slate-200"
                                >
                                    <div
                                        class="flex items-start justify-between gap-2"
                                    >
                                        <div>
                                            <div
                                                class="font-bold text-sm text-slate-800"
                                            >
                                                {{
                                                    itemTitle(
                                                        item.id
                                                    )
                                                }}
                                            </div>

                                            <div
                                                class="text-xs text-slate-400 mt-1"
                                            >
                                                {{
                                                    itemSubtitle(
                                                        item.id
                                                    )
                                                }}
                                            </div>
                                        </div>

                                        <span
                                            :class="[
                                                'px-2 py-1 rounded-full text-[8px] font-bold',
                                                itemTypeBadge(item.id).classes
                                            ]"
                                        >
                                            {{
                                                itemTypeBadge(item.id).text
                                            }}
                                        </span>
                                    </div>
                                </td>

                                <!-- ========================================= -->
                                <!-- Формат -->
                                <!-- ========================================= -->

                                <td
                                    class="px-3 py-3 border-r border-b border-slate-200"
                                >
                                    <textarea
                                        v-model="item.format"
                                        rows="3"
                                        placeholder="Ролик, программа, интеграция..."
                                        class="w-full rounded-lg border-slate-300 text-sm"
                                    ></textarea>

                                    <input
                                        v-model="item.materials_url"
                                        type="text"
                                        placeholder="Ссылка на сценарий или материалы"
                                        class="mt-2 w-full rounded-lg border-slate-300 text-xs"
                                    >

                                    <p
                                        v-if="
                                            itemError(
                                                index,
                                                'materials_url'
                                            )
                                        "
                                        class="mt-1 text-[10px] text-rose-600"
                                    >
                                        {{
                                            itemError(
                                                index,
                                                'materials_url'
                                            )
                                        }}
                                    </p>
                                </td>

                                <!-- ========================================= -->
                                <!-- Хронометраж -->
                                <!-- ========================================= -->

                                <td
                                    class="px-3 py-3 border-r border-b border-slate-200"
                                >
                                    <input
                                        v-model.number="item.duration_seconds"
                                        type="number"
                                        min="0"
                                        :disabled="
                                            getMeta(item.id)?.type
                                            !== 'radio'
                                        "
                                        class="w-full rounded-lg border-slate-300 text-sm disabled:bg-slate-100 disabled:text-slate-400"
                                    >

                                    <div
                                        v-if="
                                            getMeta(item.id)?.type
                                            === 'radio'
                                        "
                                        class="text-[10px] text-slate-400 mt-1"
                                    >
                                        секунд
                                    </div>

                                    <div
                                        v-else
                                        class="text-[10px] text-slate-400 mt-1"
                                    >
                                        не используется
                                    </div>
                                </td>

                                <!-- ========================================= -->
                                <!-- Выходы -->
                                <!-- ========================================= -->

                                <td
                                    class="px-3 py-3 border-r border-b border-slate-200"
                                >
                                    <input
                                        v-model.number="item.outputs_per_day"
                                        type="number"
                                        min="0"
                                        class="w-full rounded-lg border-slate-300 text-sm"
                                    >
                                </td>

                                <!-- ========================================= -->
                                <!-- Дни -->
                                <!-- ========================================= -->

                                <td
                                    class="px-3 py-3 border-r border-b border-slate-200"
                                >
                                    <input
                                        v-model.number="item.days_count"
                                        type="number"
                                        min="0"
                                        class="w-full rounded-lg border-slate-300 text-sm"
                                    >
                                </td>

                                <!-- ========================================= -->
                                <!-- Всего выходов -->
                                <!-- ========================================= -->

                                <td
                                    class="px-3 py-3 border-r border-b border-slate-200 bg-slate-50"
                                >
                                    <div
                                        class="font-bold text-center text-slate-700 mt-2"
                                    >
                                        {{
                                            calculateTotalOutputs(
                                                item
                                            )
                                        }}
                                    </div>

                                    <div
                                        class="text-[10px] text-center text-slate-400 mt-1"
                                    >
                                        выходов
                                    </div>
                                </td>

                                <!-- ========================================= -->
                                <!-- Цена -->
                                <!-- ========================================= -->

                                <td
                                    class="px-3 py-3 border-r border-b border-slate-200"
                                >

                                    <!-- Радио -->

                                    <template
                                        v-if="
                                            getMeta(item.id)?.type
                                            === 'radio'
                                        "
                                    >
                                        <input
                                            v-model.number="item.price_per_second"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="w-full rounded-lg border-slate-300 text-sm"
                                        >

                                        <div
                                            class="text-[10px] text-slate-400 mt-1"
                                        >
                                            ₽ / сек.
                                        </div>
                                    </template>

                                    <!-- Digital / offline -->

                                    <template
                                        v-else
                                    >
                                        <input
                                            v-model.number="item.fixed_price"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="w-full rounded-lg border-slate-300 text-sm"
                                        >

                                        <div
                                            class="text-[10px] text-slate-400 mt-1"
                                        >
                                            фиксированная цена, ₽
                                        </div>
                                    </template>
                                </td>

                                <!-- ========================================= -->
                                <!-- Стоимость -->
                                <!-- ========================================= -->

                                <td
                                    class="px-3 py-3 border-r border-b border-slate-200 bg-emerald-50/40"
                                >
                                    <div
                                        class="font-bold text-emerald-700 mt-2 whitespace-nowrap"
                                    >
                                        {{
                                            money(
                                                calculateItemPrice(
                                                    item
                                                )
                                            )
                                        }}
                                        ₽
                                    </div>

                                    <div
                                        v-if="
                                            getMeta(item.id)?.type
                                            === 'radio'
                                        "
                                        class="text-[9px] text-emerald-600/70 mt-1"
                                    >
                                        автоматически
                                    </div>
                                </td>

                                <!-- ========================================= -->
                                <!-- KPI -->
                                <!-- ========================================= -->

                                <td
                                    class="px-3 py-3 border-r border-b border-slate-200"
                                >
                                    <textarea
                                        v-model="item.kpi"
                                        rows="4"
                                        placeholder="Охват, прослушивания, переходы, заявки..."
                                        class="w-full rounded-lg border-slate-300 text-sm"
                                    ></textarea>
                                </td>

                                <!-- ========================================= -->
                                <!-- Ответственные -->
                                <!-- ========================================= -->

                                <td
                                    class="px-3 py-3 border-r border-b border-slate-200"
                                >
                                    <div
                                        v-if="availableResponsibles.length"
                                        class="space-y-1.5 max-h-[180px] overflow-y-auto pr-1"
                                    >
                                        <label
                                            v-for="user in availableResponsibles"
                                            :key="user.id"
                                            class="flex items-center gap-2 p-2 rounded-lg cursor-pointer border transition"
                                            :class="
                                                item.responsible_ids
                                                    .map(Number)
                                                    .includes(
                                                        Number(
                                                            user.id
                                                        )
                                                    )
                                                    ? 'bg-indigo-50 border-indigo-200'
                                                    : 'border-transparent hover:bg-slate-50'
                                            "
                                        >
                                            <input
                                            v-if="canEdit"
                                                v-model="item.responsible_ids"
                                                type="checkbox"
                                                :value="Number(user.id)"
                                                class="rounded text-indigo-600 focus:ring-indigo-500"
                                            >

                                            <div
                                                class="w-7 h-7 shrink-0 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-[10px] font-bold"
                                            >
                                                {{
                                                    getInitials(
                                                        user.name
                                                    )
                                                }}
                                            </div>

                                            <span
                                                class="text-xs font-medium text-slate-700 truncate"
                                            >
                                                {{ user.name }}
                                            </span>
                                        </label>
                                    </div>

                                    <div
                                        v-else
                                        class="text-xs text-slate-400 italic p-2"
                                    >
                                        Нет пользователей с доступом
                                    </div>

                                    <p
                                        v-if="
                                            itemError(
                                                index,
                                                'responsible_ids'
                                            )
                                        "
                                        class="mt-2 text-[10px] text-rose-600"
                                    >
                                        {{
                                            itemError(
                                                index,
                                                'responsible_ids'
                                            )
                                        }}
                                    </p>

                                    <div
                                        v-if="
                                            item.responsible_ids.length
                                        "
                                        class="mt-2 pt-2 border-t border-slate-100 text-[10px] text-slate-400"
                                    >
                                        Выбрано:
                                        {{
                                            item.responsible_ids.length
                                        }}
                                    </div>
                                </td>

                                <!-- ========================================= -->
                                <!-- Сроки -->
                                <!-- ========================================= -->

                                <td
                                    class="px-3 py-3 border-r border-b border-slate-200"
                                >
                                    <label
                                        class="block text-[10px] uppercase text-slate-400"
                                    >
                                        Начало
                                    </label>

                                    <input
                                        v-model="item.start_date"
                                        type="date"
                                        class="mt-1 w-full rounded-lg border-slate-300 text-sm"
                                    >

                                    <label
                                        class="block text-[10px] uppercase text-slate-400 mt-3"
                                    >
                                        Завершение
                                    </label>

                                    <input
                                        v-model="item.end_date"
                                        type="date"
                                        class="mt-1 w-full rounded-lg border-slate-300 text-sm"
                                    >

                                    <p
                                        v-if="
                                            itemError(
                                                index,
                                                'end_date'
                                            )
                                        "
                                        class="mt-1 text-[10px] text-rose-600"
                                    >
                                        {{
                                            itemError(
                                                index,
                                                'end_date'
                                            )
                                        }}
                                    </p>
                                </td>

                                <!-- ========================================= -->
                                <!-- Удалить -->
                                <!-- ========================================= -->

                                <td
                                    class="px-3 py-3 border-b border-slate-200 text-center"
                                >
                                    <button
                                    v-if="canEdit"
                                        type="button"
                                        title="Удалить строку"
                                        @click="
                                            deleteItem(
                                                item.id
                                            )
                                        "
                                        class="w-8 h-8 rounded-full text-rose-500 hover:bg-rose-50 transition"
                                    >
                                        <i
                                            class="fas fa-trash-alt"
                                        ></i>
                                    </button>
                                </td>

                            </tr>
                        </tbody>

                        <!-- ================================================= -->
                        <!-- FOOTER -->
                        <!-- ================================================= -->

                        <tfoot>
                            <tr
                                class="bg-slate-900 text-white"
                            >
                                <td
                                    colspan="5"
                                    class="sticky left-0 z-10 bg-slate-900 px-4 py-4 font-bold uppercase"
                                >
                                    Итого
                                </td>

                                <td
                                    class="px-3 py-4 font-bold"
                                >
                                    {{ totalOutputs }}
                                </td>

                                <td></td>

                                <td
                                    class="px-3 py-4 font-bold text-emerald-300 whitespace-nowrap"
                                >
                                    {{
                                        money(
                                            calculatedTotal
                                        )
                                    }}
                                    ₽
                                </td>

                                <td colspan="4"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>
        </main>

        <!-- ========================================================= -->
        <!-- МОДАЛЬНОЕ ОКНО ДОБАВЛЕНИЯ -->
        <!-- ========================================================= -->

        <Teleport to="body">
            <div
                v-if="isAddModalOpen"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4"
            >

                <!-- Overlay -->

                <button
                    type="button"
                    class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm"
                    aria-label="Закрыть"
                    @click="closeAddModal"
                ></button>

                <!-- Modal -->

                <div
                    class="relative z-10 w-full max-w-lg bg-white rounded-3xl shadow-2xl overflow-hidden"
                >

                    <!-- Header -->

                    <div
                        class="px-6 py-5 border-b border-slate-100 flex justify-between items-center"
                    >
                        <div>
                            <h2
                                class="text-xl font-bold text-slate-800"
                            >
                                Добавить строку
                            </h2>

                            <p
                                class="text-xs text-slate-400 mt-1"
                            >
                                Новая активность медиаплана
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="closeAddModal"
                            class="w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600"
                        >
                            ×
                        </button>
                    </div>

                    <!-- Form -->

                    <form
                        class="p-6 space-y-5"
                        @submit.prevent="
                            submitNewItem
                        "
                    >

                        <!-- Тип -->

                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700"
                            >
                                Тип строки
                            </label>

                            <select
                                v-model="
                                    addItemForm.type
                                "
                                class="mt-1 w-full rounded-xl border-slate-300"
                            >
                                <option
                                    value="radio"
                                >
                                    Радиостанция
                                </option>

                                <option
                                    v-for="option in activityOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Радио -->

                        <template
                            v-if="
                                addItemForm.type
                                === 'radio'
                            "
                        >

                            <!-- Город -->

                            <div>
                                <label
                                    class="block text-sm font-medium text-slate-700"
                                >
                                    Город
                                </label>

                                <select
                                    v-model="
                                        addItemForm.city_id
                                    "
                                    class="mt-1 w-full rounded-xl border-slate-300"
                                >
                                    <option
                                        :value="null"
                                    >
                                        Выберите город
                                    </option>

                                    <option
                                        v-for="city in cities"
                                        :key="city.id"
                                        :value="city.id"
                                    >
                                        {{ city.name }}
                                    </option>
                                </select>

                                <p
                                    v-if="
                                        addItemForm.errors
                                            .city_id
                                    "
                                    class="mt-1 text-xs text-rose-600"
                                >
                                    {{
                                        addItemForm.errors
                                            .city_id
                                    }}
                                </p>
                            </div>

                            <!-- Станция -->

                            <div>
                                <label
                                    class="block text-sm font-medium text-slate-700"
                                >
                                    Радиостанция
                                </label>

                                <select
                                    v-model="
                                        addItemForm
                                            .radio_station_id
                                    "
                                    :disabled="
                                        !addItemForm
                                            .city_id
                                    "
                                    class="mt-1 w-full rounded-xl border-slate-300 disabled:bg-slate-100"
                                >
                                    <option
                                        :value="null"
                                    >
                                        Выберите радиостанцию
                                    </option>

                                    <option
                                        v-for="station in availableStations"
                                        :key="station.id"
                                        :value="station.id"
                                    >
                                        {{ station.name }}

                                        {{
                                            station.frequency
                                                ? `— ${station.frequency} FM`
                                                : ''
                                        }}
                                    </option>
                                </select>

                                <p
                                    v-if="
                                        addItemForm.errors
                                            .radio_station_id
                                    "
                                    class="mt-1 text-xs text-rose-600"
                                >
                                    {{
                                        addItemForm.errors
                                            .radio_station_id
                                    }}
                                </p>

                                <div
                                    v-if="
                                        addItemForm.city_id
                                        &&
                                        !availableStations.length
                                    "
                                    class="mt-2 text-xs text-slate-400"
                                >
                                    Для этого города радиостанции не добавлены.
                                </div>
                            </div>
                        </template>

                        <!-- Не радио -->

                        <div
                            v-else
                        >
                            <label
                                class="block text-sm font-medium text-slate-700"
                            >
                                Название платформы или активности
                            </label>

                            <input
                                v-model="
                                    addItemForm
                                        .platform_name
                                "
                                type="text"
                                placeholder="Например: Telegram, VK Видео, очная встреча"
                                class="mt-1 w-full rounded-xl border-slate-300"
                            >

                            <p
                                v-if="
                                    addItemForm.errors
                                        .platform_name
                                "
                                class="mt-1 text-xs text-rose-600"
                            >
                                {{
                                    addItemForm.errors
                                        .platform_name
                                }}
                            </p>
                        </div>

                        <!-- Buttons -->

                        <div
                            class="flex justify-end gap-3 pt-3 border-t border-slate-100"
                        >
                            <button
                                type="button"
                                @click="
                                    closeAddModal
                                "
                                class="px-5 py-2.5 rounded-xl border border-slate-300 text-slate-600 hover:bg-slate-50"
                            >
                                Отмена
                            </button>

                            <button
                                type="submit"
                                :disabled="
                                    addItemForm.processing
                                "
                                class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold disabled:opacity-50"
                            >
                                {{
                                    addItemForm.processing
                                        ? 'Добавление...'
                                        : 'Добавить'
                                }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>