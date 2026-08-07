<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Klient;
use App\Models\MediaPlan;
use App\Models\MediaPlanItem;
use App\Models\RadioStation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use App\Jobs\SendMediaPlanResponsibleNotification;

class MediaPlanController extends Controller
{
    /**
     * Создание медиаплана.
     */
    public function store(Request $request, Klient $klient)
    {
        /*
         * Пока оставляем создание доступным тем,
         * кто имеет право просмотра клиента.
         */
        $this->authorize('view', $klient);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'start_date' => [
                'nullable',
                'date',
            ],

            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],

            'city_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'city_ids.*' => [
                'integer',
                'exists:cities,id',
            ],

            'radio_station_ids' => [
                'nullable',
                'array',
            ],

            'radio_station_ids.*' => [
                'integer',
                'exists:radio_stations,id',
            ],

            'multiplatform_activities' => [
                'nullable',
                'array',
            ],

            'multiplatform_activities.*' => [
                'string',
                Rule::in([
                    'vk',
                    'ok',
                    'telegram',
                    'vk_video',
                    'rutube',
                    'max',
                    'offline_meeting',
                    'local_award',
                ]),
            ],
        ]);

        /*
         * Проверяем соответствие радиостанций городам.
         */
        $allowedStationIds = RadioStation::query()
            ->whereIn('city_id', $validated['city_ids'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        $selectedStationIds = collect(
            $validated['radio_station_ids'] ?? []
        )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $invalidStationIds = $selectedStationIds->diff(
            $allowedStationIds
        );

        if ($invalidStationIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'radio_station_ids' =>
                    'Одна или несколько радиостанций не относятся к выбранным городам.',
            ]);
        }

        $mediaPlan = DB::transaction(function () use (
            $validated,
            $klient,
            $selectedStationIds
        ) {
            $mediaPlan = $klient->mediaPlans()->create([
                'creator_id' => Auth::id(),
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'status' => 'draft',
                'total_amount' => 0,
            ]);

            $mediaPlan->cities()->sync(
                $validated['city_ids']
            );

            $stations = RadioStation::query()
                ->whereIn('id', $selectedStationIds)
                ->get();

            $sortOrder = 1;

            foreach ($stations as $station) {
                $mediaPlan->items()->create([
                    'sort_order' => $sortOrder++,
                    'type' => 'radio',
                    'city_id' => $station->city_id,
                    'radio_station_id' => $station->id,
                    'price_per_second' =>
                        $station->price_per_second ?? 0,
                    'fixed_price' => 0,
                    'total_price' => 0,
                    'start_date' =>
                        $validated['start_date'] ?? null,
                    'end_date' =>
                        $validated['end_date'] ?? null,
                ]);
            }

            $platformLabels = [
                'vk' => 'VK',
                'ok' => 'Одноклассники',
                'telegram' => 'Telegram',
                'vk_video' => 'VK Видео',
                'rutube' => 'RuTube',
                'max' => 'Макс',
                'offline_meeting' => 'Очные встречи',
                'local_award' =>
                    'Локальные финалы и награждения',
            ];

            foreach (
                $validated['multiplatform_activities'] ?? []
                as $activity
            ) {
                $mediaPlan->items()->create([
                    'sort_order' => $sortOrder++,

                    'type' => in_array(
                        $activity,
                        [
                            'offline_meeting',
                            'local_award',
                        ],
                        true
                    )
                        ? 'offline'
                        : 'social',

                    'platform_name' =>
                        $platformLabels[$activity],

                    'fixed_price' => 0,
                    'total_price' => 0,

                    'start_date' =>
                        $validated['start_date'] ?? null,

                    'end_date' =>
                        $validated['end_date'] ?? null,
                ]);
            }

            return $mediaPlan;
        });

        return redirect()
            ->route(
                'media-plans.show',
                $mediaPlan->id
            )
            ->with(
                'success',
                'Медиаплан создан. Заполните параметры размещения.'
            );
    }

    /**
     * Просмотр медиаплана.
     */
    public function show(MediaPlan $mediaPlan)
    {
        $this->authorizeMediaPlanView(
            $mediaPlan
        );

        $mediaPlan->load([
            /*
             * ВАЖНО:
             * company_id нужен для загрузки компании.
             */
            'klient:id,name,user_id,company_id',

            'klient.company:id,name,user_id',

            'klient.creator:id,name',

            'klient.allowedUsers:id,name',

            'creator:id,name',

            'cities:id,name',

            'items' => fn ($query) =>
                $query
                    ->orderBy('sort_order')
                    ->orderBy('id'),

            'items.city:id,name',

            'items.radioStation:id,city_id,name,frequency,price_per_second',

            'items.responsibles:id,name',
        ]);

        /*
         * Пользователи, которых можно назначать
         * ответственными:
         *
         * создатель клиента + klient_access.
         */
        $availableResponsibles = collect([
            $mediaPlan->klient->creator,
        ])
            ->merge(
                $mediaPlan->klient->allowedUsers
            )
            ->filter()
            ->unique('id')
            ->values()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                ];
            });

        /*
         * Можно ли текущему пользователю
         * изменять медиаплан.
         */
        $userId = (int) Auth::id();

        $isMediaPlanCreator =
            (int) $mediaPlan->creator_id
            === $userId;

        $isCompanyOwner =
            $mediaPlan->klient->company
            &&
            (int) $mediaPlan
                ->klient
                ->company
                ->user_id
            === $userId;

        $canEdit =
            $isMediaPlanCreator
            || $isCompanyOwner;

        $cities = City::query()
            ->with([
                'radioStations' =>
                    fn ($query) =>
                        $query
                            ->select(
                                'id',
                                'city_id',
                                'name',
                                'frequency',
                                'price_per_second'
                            )
                            ->orderBy('name'),
            ])
            ->orderBy('name')
            ->get();

        return Inertia::render(
            'MediaPlans/Show',
            [
                'mediaPlan' =>
                    $mediaPlan,

                'cities' =>
                    $cities,

                'availableResponsibles' =>
                    $availableResponsibles,

                /*
                 * Используем во Vue,
                 * чтобы скрывать редактирование.
                 */
                'canEdit' =>
                    $canEdit,
            ]
        );
    }

    /**
     * Обновление медиаплана.
     *
     * Только создатель медиаплана
     * или владелец компании.
     */
    public function update(
    Request $request,
    MediaPlan $mediaPlan
) {
    $this->authorizeMediaPlanEdit(
        $mediaPlan
    );

    $validated = $request->validate([
        'name' => [
            'required',
            'string',
            'max:255',
        ],

        'description' => [
            'nullable',
            'string',
        ],

        'status' => [
            'required',
            Rule::in([
                'draft',
                'active',
                'completed',
                'cancelled',
            ]),
        ],

        'start_date' => [
            'nullable',
            'date',
        ],

        'end_date' => [
            'nullable',
            'date',
            'after_or_equal:start_date',
        ],

        'items' => [
            'required',
            'array',
        ],

        'items.*.id' => [
            'required',
            'integer',
        ],

        'items.*.format' => [
            'nullable',
            'string',
        ],

        'items.*.materials_url' => [
            'nullable',
            'string',
            'max:2000',
        ],

        'items.*.duration_seconds' => [
            'nullable',
            'integer',
            'min:0',
            'max:86400',
        ],

        'items.*.outputs_per_day' => [
            'nullable',
            'integer',
            'min:0',
            'max:100000',
        ],

        'items.*.days_count' => [
            'nullable',
            'integer',
            'min:0',
            'max:10000',
        ],

        'items.*.price_per_second' => [
            'nullable',
            'numeric',
            'min:0',
        ],

        'items.*.fixed_price' => [
            'nullable',
            'numeric',
            'min:0',
        ],

        'items.*.kpi' => [
            'nullable',
            'string',
        ],

        'items.*.responsible_ids' => [
            'nullable',
            'array',
        ],

        'items.*.responsible_ids.*' => [
            'integer',
            'exists:users,id',
        ],

        'items.*.start_date' => [
            'nullable',
            'date',
        ],

        'items.*.end_date' => [
            'nullable',
            'date',
        ],
    ]);

    /*
     * Получаем реальные строки медиаплана.
     */
    $existingItems = $mediaPlan
        ->items()
        ->get()
        ->keyBy('id');

    $submittedIds = collect(
        $validated['items']
    )
        ->pluck('id')
        ->map(fn ($id) => (int) $id);

    $existingItemIds = $existingItems
        ->keys()
        ->map(fn ($id) => (int) $id);

    $foreignIds = $submittedIds->diff(
        $existingItemIds
    );

    if ($foreignIds->isNotEmpty()) {
        throw ValidationException::withMessages([
            'items' =>
                'Обнаружены строки, которые не относятся к этому медиаплану.',
        ]);
    }

    /*
     * Пользователи, которых разрешено
     * назначать ответственными.
     */
    $allowedResponsibleIds = DB::table(
        'klient_access'
    )
        ->where(
            'klient_id',
            $mediaPlan->klient_id
        )
        ->pluck('user_id')
        ->map(fn ($id) => (int) $id);

    /*
     * Добавляем создателя клиента.
     */
    $clientOwnerId = Klient::query()
        ->whereKey(
            $mediaPlan->klient_id
        )
        ->value('user_id');

    if ($clientOwnerId) {
        $allowedResponsibleIds->push(
            (int) $clientOwnerId
        );
    }

    $allowedResponsibleIds =
        $allowedResponsibleIds
            ->unique()
            ->values();

    /*
     * Проверяем ответственных каждой строки.
     */
    foreach (
        $validated['items']
        as $index => $itemData
    ) {
        $responsibleIds = collect(
            $itemData['responsible_ids'] ?? []
        )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $invalidResponsibleIds =
            $responsibleIds->diff(
                $allowedResponsibleIds
            );

        if ($invalidResponsibleIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                "items.$index.responsible_ids" =>
                    'Один или несколько выбранных пользователей не имеют доступа к клиенту.',
            ]);
        }
    }

    /*
     * Сюда складываем уведомления.
     *
     * Job запустим только после успешного
     * завершения транзакции.
     */
    $notificationsToSend = [];

    DB::transaction(function () use (
        $validated,
        $mediaPlan,
        $existingItems,
        &$notificationsToSend
    ) {
        /*
         * Обновляем основные данные медиаплана.
         */
        $mediaPlan->update([
            'name' =>
                $validated['name'],

            'description' =>
                $validated['description'] ?? null,

            'status' =>
                $validated['status'],

            'start_date' =>
                $validated['start_date'] ?? null,

            'end_date' =>
                $validated['end_date'] ?? null,
        ]);

        $planTotal = 0;

        foreach (
            $validated['items']
            as $index => $itemData
        ) {
            /** @var MediaPlanItem|null $item */
            $item = $existingItems->get(
                (int) $itemData['id']
            );

            if (!$item) {
                continue;
            }

            /*
             * -----------------------------
             * Сначала получаем старых
             * ответственных.
             * -----------------------------
             */
            $oldResponsibleIds = $item
                ->responsibles()
                ->pluck('users.id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            /*
             * Новые ответственные,
             * пришедшие из формы.
             */
            $newResponsibleIds = collect(
                $itemData['responsible_ids'] ?? []
            )
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            /*
             * Определяем только новых людей.
             *
             * Например:
             *
             * было: [5, 7]
             * стало: [5, 7, 9]
             *
             * письмо получит только 9.
             */
            $justAssignedIds =
                $newResponsibleIds->diff(
                    $oldResponsibleIds
                );

            /*
             * -----------------------------
             * Расчёты строки.
             * -----------------------------
             */

            $duration = (int) (
                $itemData['duration_seconds'] ?? 0
            );

            $outputsPerDay = (int) (
                $itemData['outputs_per_day'] ?? 0
            );

            $daysCount = (int) (
                $itemData['days_count'] ?? 0
            );

            $pricePerSecond = (float) (
                $itemData['price_per_second'] ?? 0
            );

            $fixedPrice = (float) (
                $itemData['fixed_price'] ?? 0
            );

            /*
             * Количество выходов.
             */
            $totalOutputs =
                $outputsPerDay
                * $daysCount;

            /*
             * Стоимость.
             */
            if ($item->type === 'radio') {
                $totalPrice =
                    $pricePerSecond
                    * $duration
                    * $totalOutputs;
            } else {
                $totalPrice =
                    $fixedPrice;
            }

            $totalPrice = round(
                $totalPrice,
                2
            );

            /*
             * -----------------------------
             * Обновляем строку.
             * -----------------------------
             */
            $item->update([
                'sort_order' =>
                    $index + 1,

                'format' =>
                    $itemData['format'] ?? null,

                'materials_url' =>
                    $itemData['materials_url'] ?? null,

                'duration_seconds' =>
                    $duration,

                'outputs_per_day' =>
                    $outputsPerDay,

                'days_count' =>
                    $daysCount,

                'total_outputs' =>
                    $totalOutputs,

                'price_per_second' =>
                    $pricePerSecond,

                'fixed_price' =>
                    $fixedPrice,

                'total_price' =>
                    $totalPrice,

                'kpi' =>
                    $itemData['kpi'] ?? null,

                'start_date' =>
                    $itemData['start_date'] ?? null,

                'end_date' =>
                    $itemData['end_date'] ?? null,
            ]);

            /*
             * -----------------------------
             * Обновляем ответственных.
             * -----------------------------
             */
            $item
                ->responsibles()
                ->sync(
                    $newResponsibleIds
                        ->toArray()
                );

            /*
             * Запоминаем уведомления,
             * но пока НЕ отправляем.
             */
            foreach (
                $justAssignedIds
                as $responsibleUserId
            ) {
                $notificationsToSend[] = [
                    'item_id' =>
                        (int) $item->id,

                    'user_id' =>
                        (int) $responsibleUserId,
                ];
            }

            $planTotal +=
                $totalPrice;
        }

        /*
         * Общий бюджет медиаплана.
         */
        $mediaPlan->update([
            'total_amount' =>
                round(
                    $planTotal,
                    2
                ),
        ]);
    });

    /*
     * ------------------------------------------------
     * Транзакция успешно завершилась.
     *
     * Только теперь отправляем Job.
     * ------------------------------------------------
     */
    foreach (
        $notificationsToSend
        as $notification
    ) {
        SendMediaPlanResponsibleNotification::dispatch(
            $notification['item_id'],
            $notification['user_id']
        );
    }

    return back()->with(
        'success',
        'Медиаплан сохранён'
    );
}

    /**
     * Добавление строки.
     *
     * Только создатель медиаплана
     * или владелец компании.
     */
    public function storeItem(
        Request $request,
        MediaPlan $mediaPlan
    ) {
        $this->authorizeMediaPlanEdit(
            $mediaPlan
        );

        $validated = $request->validate([
            'type' => [
                'required',
                Rule::in([
                    'radio',
                    'social',
                    'offline',
                    'other',
                ]),
            ],

            'city_id' => [
                'nullable',
                'integer',
                'exists:cities,id',
            ],

            'radio_station_id' => [
                'nullable',
                'integer',
                'exists:radio_stations,id',
            ],

            'platform_name' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $station = null;

        if (
            $validated['type']
            === 'radio'
        ) {
            if (
                empty(
                    $validated['city_id']
                )
                ||
                empty(
                    $validated[
                        'radio_station_id'
                    ]
                )
            ) {
                throw ValidationException::withMessages([
                    'radio_station_id' =>
                        'Для радио необходимо выбрать город и радиостанцию.',
                ]);
            }

            $station = RadioStation::query()
                ->whereKey(
                    $validated[
                        'radio_station_id'
                    ]
                )
                ->where(
                    'city_id',
                    $validated['city_id']
                )
                ->first();

            if (!$station) {
                throw ValidationException::withMessages([
                    'radio_station_id' =>
                        'Выбранная радиостанция не относится к указанному городу.',
                ]);
            }
        } else {
            if (
                empty(
                    $validated[
                        'platform_name'
                    ]
                )
            ) {
                throw ValidationException::withMessages([
                    'platform_name' =>
                        'Укажите название платформы или активности.',
                ]);
            }
        }

        $lastSortOrder = $mediaPlan
            ->items()
            ->max('sort_order') ?? 0;

        $mediaPlan->items()->create([
            'sort_order' =>
                $lastSortOrder + 1,

            'type' =>
                $validated['type'],

            'city_id' =>
                $validated['type'] === 'radio'
                    ? $validated['city_id']
                    : null,

            'radio_station_id' =>
                $validated['type'] === 'radio'
                    ? $validated[
                        'radio_station_id'
                    ]
                    : null,

            'platform_name' =>
                $validated['type'] !== 'radio'
                    ? $validated[
                        'platform_name'
                    ]
                    : null,

            'price_per_second' =>
                $station
                    ? (
                        $station
                            ->price_per_second
                        ?? 0
                    )
                    : 0,

            'fixed_price' => 0,

            'total_price' => 0,

            'start_date' =>
                $mediaPlan->start_date,

            'end_date' =>
                $mediaPlan->end_date,
        ]);

        return back()->with(
            'success',
            'Строка медиаплана добавлена'
        );
    }

    /**
     * Удаление строки.
     *
     * Только создатель медиаплана
     * или владелец компании.
     */
    public function destroyItem(
        MediaPlan $mediaPlan,
        MediaPlanItem $item
    ) {
        $this->authorizeMediaPlanEdit(
            $mediaPlan
        );

        /*
         * Строка должна принадлежать
         * этому медиаплану.
         */
        abort_unless(
            (int) $item->media_plan_id
            === (int) $mediaPlan->id,
            404
        );

        DB::transaction(function () use (
            $mediaPlan,
            $item
        ) {
            $item->delete();

            $mediaPlan->update([
                'total_amount' =>
                    $mediaPlan
                        ->items()
                        ->sum(
                            'total_price'
                        ),
            ]);
        });

        return back()->with(
            'success',
            'Строка медиаплана удалена'
        );
    }


    public function destroy(MediaPlan $mediaPlan)
{
    $this->authorizeMediaPlanEdit($mediaPlan);

    /*
     * Сохраняем ID клиента до удаления,
     * чтобы потом вернуться в его карточку.
     */
    $klientId = $mediaPlan->klient_id;

    DB::transaction(function () use ($mediaPlan) {

        /*
         * Если в миграциях для:
         *
         * media_plan_items.media_plan_id
         * media_plan_city.media_plan_id
         *
         * стоит cascadeOnDelete(),
         * строки и связи удалятся автоматически.
         */

        $mediaPlan->delete();
    });

    return redirect()
        ->route('klients.show', $klientId)
        ->with(
            'success',
            'Медиаплан удалён'
        );
}

    /**
     * ПРАВО ПРОСМОТРА.
     *
     * Смотреть могут:
     * 1. Создатель медиаплана.
     * 2. Создатель карточки клиента.
     * 3. Пользователь из klient_access.
     * 4. Владелец компании клиента.
     */
    private function authorizeMediaPlanView(
        MediaPlan $mediaPlan
    ): void {
        $mediaPlan->loadMissing([
            'klient.allowedUsers:id',
            'klient.company:id,user_id',
        ]);

        $userId = (int) Auth::id();

        $isMediaPlanCreator =
            (int) $mediaPlan->creator_id
            === $userId;

        $isClientCreator =
            (int) $mediaPlan
                ->klient
                ->user_id
            === $userId;

        $hasClientAccess =
            $mediaPlan
                ->klient
                ->allowedUsers
                ->contains(
                    'id',
                    $userId
                );

        $isCompanyOwner =
            $mediaPlan->klient->company
            &&
            (int) $mediaPlan
                ->klient
                ->company
                ->user_id
            === $userId;

        abort_unless(
            $isMediaPlanCreator
            || $isClientCreator
            || $hasClientAccess
            || $isCompanyOwner,
            403,
            'У вас нет доступа к этому медиаплану.'
        );
    }

    /**
     * ПРАВО РЕДАКТИРОВАНИЯ.
     *
     * Изменять могут ТОЛЬКО:
     *
     * 1. Создатель медиаплана.
     * 2. Владелец компании клиента.
     *
     * Пользователь из klient_access может
     * только смотреть.
     */
    private function authorizeMediaPlanEdit(
        MediaPlan $mediaPlan
    ): void {
        $mediaPlan->loadMissing([
            'klient.company:id,user_id',
        ]);

        $userId = (int) Auth::id();

        /*
         * Создатель медиаплана.
         */
        $isMediaPlanCreator =
            (int) $mediaPlan->creator_id
            === $userId;

        /*
         * Владелец компании клиента.
         */
        $isCompanyOwner =
            $mediaPlan->klient->company
            &&
            (int) $mediaPlan
                ->klient
                ->company
                ->user_id
            === $userId;

        abort_unless(
            $isMediaPlanCreator
            || $isCompanyOwner,
            403,
            'Изменять медиаплан может только его создатель или владелец компании.'
        );
    }
}