<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use App\Models\PollProblem;
use App\Models\PollProblemComment;
use App\Models\PollParticipant;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class PollController extends Controller
{
// app/Http/Controllers/Api/PollController.php
    // app/Http/Controllers/Api/PollController.php
    public function getCompanyUsers($companyId)
    {
        $company = Company::findOrFail($companyId);
        $ownerId = $company->user_id; // 🔥 Владелец этой конкретной компании

        // Получаем владельца этой компании
        $owner = \App\Models\User::find($ownerId);

        // Получаем всех пользователей из pivot
        $pivotUsers = $company->users()
            ->select('users.id', 'users.name', 'users.email', 'company_user.role')
            ->get();

        $result = [];

        // 🔥 Добавляем владельца этой компании (если он есть)
        if ($owner) {
            $result[] = [
                'id' => $owner->id,
                'name' => $owner->name,
                'email' => $owner->email,
                'role' => 'owner',
                'is_owner' => true
            ];
        }

        // 🔥 Добавляем остальных пользователей (исключая владельца этой компании)
        foreach ($pivotUsers as $user) {
            if ($user->id !== $ownerId) {
                $result[] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->pivot->role ?? 'member',
                    'is_owner' => false
                ];
            }
        }

        // Сортируем по имени
        usort($result, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return response()->json($result);
    }

    public function index($companyId)
    {
        $company = Company::findOrFail($companyId);
        $user = Auth::user();

        $polls = Poll::where('company_id', $companyId)
            ->with(['creator', 'participants'])
            ->withCount(['participants', 'problems'])
            ->get()
            ->map(function ($poll) use ($user, $company) {
                $poll->responded_count = $poll->participants->where('has_responded', true)->count();
                $poll->is_participant = $poll->participants->where('user_id', $user->id)->isNotEmpty();
                $poll->is_creator = $poll->created_by === $user->id;
                $poll->is_owner = $company->user_id === $user->id;
                $poll->can_delete = $poll->is_creator || $poll->is_owner;

                return $poll;
            })
            ->filter(function ($poll) use ($user, $company) {
                // Создатель и владелец видят все опросы
                if ($poll->is_creator || $poll->is_owner) {
                    return true;
                }

                // Остальные видят только те, в которых участвуют
                return $poll->is_participant;
            })
            ->values();

        return response()->json($polls);
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'participants' => 'required|array|min:1',
            'participants.*' => 'exists:users,id'
        ]);

        $user = Auth::user();
        $company = Company::findOrFail($request->company_id);

        // Проверяем, является ли пользователь участником компании
        if (!$company->isUserMember($user->id)) {
            return response()->json([
                'message' => 'Вы не являетесь участником этой компании'
            ], 403);
        }

        $poll = null;

        DB::transaction(function () use ($request, $user, $company, &$poll) {
            $poll = Poll::create([
                'company_id' => $request->company_id,
                'created_by' => $user->id,
                'title' => $request->title,
                'description' => $request->description,
                'status' => 'active'
            ]);

            foreach ($request->participants as $userId) {
                PollParticipant::create([
                    'poll_id' => $poll->id,
                    'user_id' => $userId,
                    'has_responded' => false
                ]);
            }
        });

        // 🔥 Отправляем уведомления всем участникам (синхронно)
        if ($poll) {
            $participantIds = $request->participants;

            foreach ($participantIds as $userId) {
                $participant = User::find($userId);
                if ($participant && $participant->email) {
                    try {
                        $participant->notify(new \App\Notifications\PollCreatedNotification($poll, $company));
                    } catch (\Exception $e) {
                        \Log::error("Failed to send poll notification to {$participant->email}: " . $e->getMessage());
                    }
                }
            }
        }

        return response()->json([
            'message' => 'Опрос успешно создан! Уведомления отправлены участникам.'
        ], 201);
    }

    public function show($pollId)
    {
        $poll = Poll::with([
            'creator',
            'participants.user',
            'problems.user',
            'problems.comments.user',
            'company'
        ])->findOrFail($pollId);

        $user = Auth::user();
        $company = $poll->company;

        $poll->responded_count = $poll->participants->where('has_responded', true)->count();
        $poll->is_creator = $poll->created_by === $user->id;
        $poll->is_participant = $poll->participants->where('user_id', $user->id)->isNotEmpty();
        $poll->can_respond = $poll->is_participant &&
            $poll->status === 'active';
        $poll->current_user_id = $user->id;

        // 🔥 Может ли пользователь управлять опросом (добавлять участников)
        $poll->can_manage = $poll->created_by === $user->id || $company->user_id === $user->id;
        $poll->can_delete = $poll->can_manage;

        return response()->json($poll);
    }

    public function respond(Request $request, $pollId)
    {
        $request->validate([
            'problem' => 'required|string|min:3',
            'solution' => 'required|string|min:3'
        ]);

        $user = Auth::user();
        $poll = Poll::findOrFail($pollId);

        if (!$poll->participants->where('user_id', $user->id)->isNotEmpty()) {
            return response()->json(['message' => 'Вы не участвуете в этом опросе'], 403);
        }

        if ($poll->status !== 'active') {
            return response()->json(['message' => 'Опрос уже завершен'], 403);
        }

        DB::transaction(function () use ($request, $poll, $user) {
            PollProblem::create([
                'poll_id' => $poll->id,
                'user_id' => $user->id,
                'problem' => $request->problem,
                'solution' => $request->solution,
                'is_resolved' => false
            ]);

            // Отмечаем, что пользователь ответил (только если это первый ответ)
            $participant = PollParticipant::where('poll_id', $poll->id)
                ->where('user_id', $user->id)
                ->first();

            if ($participant && !$participant->has_responded) {
                $participant->markAsResponded();
            }
        });

        return response()->json(['message' => 'Ответ сохранен']);
    }

    public function respondMultiple(Request $request, $pollId)
    {
        $request->validate([
            'responses' => 'required|array|min:1',
            'responses.*.problem' => 'required|string|min:3',
            'responses.*.solution' => 'required|string|min:3'
        ]);

        $user = Auth::user();
        $poll = Poll::findOrFail($pollId);

        if (!$poll->participants->where('user_id', $user->id)->isNotEmpty()) {
            return response()->json(['message' => 'Вы не участвуете в этом опросе'], 403);
        }

        if ($poll->status !== 'active') {
            return response()->json(['message' => 'Опрос уже завершен'], 403);
        }

        DB::transaction(function () use ($request, $poll, $user) {
            foreach ($request->responses as $response) {
                PollProblem::create([
                    'poll_id' => $poll->id,
                    'user_id' => $user->id,
                    'problem' => $response['problem'],
                    'solution' => $response['solution'],
                    'is_resolved' => false
                ]);
            }

            // Отмечаем, что пользователь ответил (только если еще не отмечен)
            $participant = PollParticipant::where('poll_id', $poll->id)
                ->where('user_id', $user->id)
                ->first();

            if ($participant && !$participant->has_responded) {
                $participant->markAsResponded();
            }
        });

        return response()->json(['message' => 'Все ответы сохранены']);
    }

    public function close($pollId)
    {
        $poll = Poll::findOrFail($pollId);
        $user = Auth::user();

        if ($poll->created_by !== $user->id) {
            return response()->json([
                'message' => 'Только создатель опроса может его закрыть'
            ], 403);
        }

        $poll->update([
            'status' => 'closed',
            'closed_at' => now()
        ]);

        return response()->json(['message' => 'Опрос закрыт']);
    }

    public function reopen($pollId)
    {
        $poll = Poll::findOrFail($pollId);
        $user = Auth::user();

        if ($poll->created_by !== $user->id) {
            return response()->json([
                'message' => 'Только создатель опроса может его переоткрыть'
            ], 403);
        }

        $poll->update([
            'status' => 'active',
            'closed_at' => null
        ]);

        return response()->json(['message' => 'Опрос переоткрыт']);
    }

    public function addComment(Request $request, $problemId)
    {
        $request->validate([
            'comment' => 'required|string|min:1|max:1000'
        ]);

        $problem = PollProblem::with('poll')->findOrFail($problemId);
        $user = Auth::user();

        $isParticipant = $problem->poll->participants()
            ->where('user_id', $user->id)
            ->exists();

        if (!$isParticipant) {
            return response()->json([
                'message' => 'Только участники опроса могут оставлять комментарии'
            ], 403);
        }

        if ($problem->poll->status !== 'active') {
            return response()->json([
                'message' => 'Опрос закрыт, комментарии недоступны'
            ], 403);
        }

        $comment = $problem->addComment($user, $request->comment);

        return response()->json([
            'message' => 'Комментарий добавлен',
            'comment' => $comment->load('user')
        ]);
    }

    public function updateComment(Request $request, $commentId)
    {
        $request->validate([
            'comment' => 'required|string|min:1|max:1000'
        ]);

        $comment = PollProblemComment::with('problem.poll')->findOrFail($commentId);
        $user = Auth::user();

        if ($comment->user_id !== $user->id) {
            return response()->json([
                'message' => 'Вы не можете редактировать этот комментарий'
            ], 403);
        }

        if ($comment->problem->poll->status !== 'active') {
            return response()->json([
                'message' => 'Опрос закрыт, редактирование недоступно'
            ], 403);
        }

        $comment->update([
            'comment' => $request->comment
        ]);

        return response()->json([
            'message' => 'Комментарий обновлен',
            'comment' => $comment->load('user')
        ]);
    }

    public function deleteComment($commentId)
    {
        $comment = PollProblemComment::with('problem.poll')->findOrFail($commentId);
        $user = Auth::user();

        if ($comment->user_id !== $user->id) {
            return response()->json([
                'message' => 'Вы не можете удалить этот комментарий'
            ], 403);
        }

        if ($comment->problem->poll->status !== 'active') {
            return response()->json([
                'message' => 'Опрос закрыт, удаление недоступно'
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'message' => 'Комментарий удален'
        ]);
    }

    public function updateProblem(Request $request, $problemId)
    {
        $request->validate([
            'problem' => 'required|string|min:3',
            'solution' => 'required|string|min:3'
        ]);

        $problem = PollProblem::with('poll')->findOrFail($problemId);
        $user = Auth::user();

        if ($problem->user_id !== $user->id) {
            return response()->json([
                'message' => 'Вы не можете редактировать эту проблему'
            ], 403);
        }

        if ($problem->poll->status !== 'active') {
            return response()->json([
                'message' => 'Опрос закрыт, редактирование недоступно'
            ], 403);
        }

        if ($problem->is_resolved) {
            return response()->json([
                'message' => 'Решенную проблему нельзя редактировать'
            ], 403);
        }

        $problem->update([
            'problem' => $request->problem,
            'solution' => $request->solution
        ]);

        return response()->json([
            'message' => 'Проблема обновлена',
            'problem' => $problem->load('user')
        ]);
    }

    public function deleteProblem($problemId)
    {
        $problem = PollProblem::with('poll')->findOrFail($problemId);
        $user = Auth::user();

        if ($problem->user_id !== $user->id) {
            return response()->json([
                'message' => 'Вы не можете удалить эту проблему'
            ], 403);
        }

        if ($problem->poll->status !== 'active') {
            return response()->json([
                'message' => 'Опрос закрыт, удаление недоступно'
            ], 403);
        }

        if ($problem->is_resolved) {
            return response()->json([
                'message' => 'Решенную проблему нельзя удалить'
            ], 403);
        }

        DB::transaction(function () use ($problem) {
            $problem->comments()->delete();
            $problem->delete();
        });

        return response()->json([
            'message' => 'Проблема удалена'
        ]);
    }

    public function resolveProblem($problemId)
    {
        $problem = PollProblem::with('poll')->findOrFail($problemId);
        $user = Auth::user();

        $isParticipant = $problem->poll->participants()
            ->where('user_id', $user->id)
            ->exists();

        if (!$isParticipant) {
            return response()->json([
                'message' => 'Только участники опроса могут отмечать решение'
            ], 403);
        }

        $problem->resolve();

        return response()->json([
            'message' => 'Проблема отмечена как решенная'
        ]);
    }

    public function destroy($pollId)
    {
        $poll = Poll::with('company')->findOrFail($pollId);
        $user = Auth::user();

        // Проверяем, является ли пользователь создателем опроса или владельцем компании
        $isCreator = $poll->created_by === $user->id;
        $isCompanyOwner = $poll->company->user_id === $user->id;

        if (!$isCreator && !$isCompanyOwner) {
            return response()->json([
                'message' => 'Только создатель опроса или владелец компании могут удалить опрос'
            ], 403);
        }

        // Проверяем, активен ли опрос (нельзя удалить активный опрос?)
        // Если хотите разрешить удаление только закрытых опросов - раскомментируйте
        // if ($poll->status === 'active') {
        //     return response()->json([
        //         'message' => 'Нельзя удалить активный опрос. Сначала закройте его.'
        //     ], 403);
        // }

        DB::transaction(function () use ($poll) {
            // Удаляем все проблемы и комментарии
            foreach ($poll->problems as $problem) {
                $problem->comments()->delete();
                $problem->delete();
            }

            // Удаляем участников
            $poll->participants()->delete();

            // Удаляем сам опрос
            $poll->delete();
        });

        return response()->json([
            'message' => 'Опрос успешно удален'
        ]);
    }


    public function addParticipants(Request $request, $pollId)
    {
        $request->validate([
            'participants' => 'required|array|min:1',
            'participants.*' => 'exists:users,id'
        ]);

        $poll = Poll::with('company')->findOrFail($pollId);
        $user = Auth::user();

        // Проверяем, может ли пользователь добавлять участников
        $isCreator = $poll->created_by === $user->id;
        $isCompanyOwner = $poll->company->user_id === $user->id;

        if (!$isCreator && !$isCompanyOwner) {
            return response()->json([
                'message' => 'Только создатель опроса или владелец компании могут добавлять участников'
            ], 403);
        }

        // Проверяем, активен ли опрос
        if ($poll->status !== 'active') {
            return response()->json([
                'message' => 'Нельзя добавлять участников в закрытый опрос'
            ], 403);
        }

        $added = [];
        $alreadyExist = [];

        DB::transaction(function () use ($request, $poll, &$added, &$alreadyExist) {
            foreach ($request->participants as $userId) {
                // Проверяем, есть ли уже такой участник
                $exists = PollParticipant::where('poll_id', $poll->id)
                    ->where('user_id', $userId)
                    ->exists();

                if ($exists) {
                    $alreadyExist[] = $userId;
                    continue;
                }

                // Добавляем участника
                PollParticipant::create([
                    'poll_id' => $poll->id,
                    'user_id' => $userId,
                    'has_responded' => false
                ]);

                $added[] = $userId;
            }
        });

        // 🔥 Отправляем уведомления новым участникам
        if (count($added) > 0) {
            $company = $poll->company;
            $addedBy = $user;

            foreach ($added as $userId) {
                $participant = User::find($userId);
                if ($participant && $participant->email) {
                    try {
                        $participant->notify(new \App\Notifications\PollParticipantAddedNotification($poll, $company, $addedBy));
                    } catch (\Exception $e) {
                        \Log::error("Failed to send participant notification to {$participant->email}: " . $e->getMessage());
                    }
                }
            }
        }

        $response = [
            'message' => 'Участники добавлены'
        ];

        if (count($added) > 0) {
            $addedUsers = User::whereIn('id', $added)->pluck('name')->implode(', ');
            $response['added'] = $addedUsers;
            $response['added_count'] = count($added);
            $response['notifications_sent'] = true;
        }

        if (count($alreadyExist) > 0) {
            $existUsers = User::whereIn('id', $alreadyExist)->pluck('name')->implode(', ');
            $response['already_exist'] = $existUsers;
            $response['already_exist_count'] = count($alreadyExist);
        }

        return response()->json($response);
    }

    /**
     * Получить список доступных для добавления участников
     */
    public function getAvailableParticipants($pollId)
    {
        $poll = Poll::with('company')->findOrFail($pollId);
        $user = Auth::user();

        // Проверяем права
        $isCreator = $poll->created_by === $user->id;
        $isCompanyOwner = $poll->company->user_id === $user->id;

        if (!$isCreator && !$isCompanyOwner) {
            return response()->json([
                'message' => 'Нет прав для просмотра'
            ], 403);
        }

        // Получаем всех участников компании
        $companyUsers = $poll->company->users()
            ->select('users.id', 'users.name', 'users.email')
            ->get();

        // Добавляем владельца компании
        $owner = User::find($poll->company->user_id);
        if ($owner && !$companyUsers->contains('id', $owner->id)) {
            $companyUsers->push($owner);
        }

        // Получаем ID уже добавленных участников
        $existingParticipantIds = $poll->participants()->pluck('user_id')->toArray();

        // Фильтруем: только те, кто еще не участвует
        $availableUsers = $companyUsers->filter(function ($user) use ($existingParticipantIds) {
            return !in_array($user->id, $existingParticipantIds);
        })->values();

        return response()->json($availableUsers);
    }



}

