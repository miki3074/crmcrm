<?php

namespace App\Http\Controllers;

use App\Jobs\SendPollCreatedNotifications;
use App\Models\Poll;
use App\Models\PollProblem;
use App\Models\PollParticipant;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PollController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $company = $user->company; // Предполагаем, что у пользователя есть метод company()

        $polls = Poll::where('company_id', $company->id)
            ->with(['creator', 'participants.user'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('polls.index', compact('polls'));
    }

    public function create()
    {
        $user = Auth::user();
        $company = $user->company;

        // Получаем всех участников компании
        $users = $company->users()->get();

        return view('polls.create', compact('company', 'users'));
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

        // 🔥 Отправляем уведомления всем участникам
        if ($poll) {
            // Загружаем участников для отправки
            $participantIds = $request->participants;

            // Отправляем уведомления в фоновом режиме
            SendPollCreatedNotifications::dispatch($poll, $company, $participantIds);
        }

        return response()->json([
            'message' => 'Опрос успешно создан!',
            'poll' => $poll
        ], 201);
    }

    public function show(Poll $poll)
    {
        $this->authorize('view', $poll);

        $poll->load(['creator', 'participants.user', 'problems.user', 'problems.comments.user']);

        // Проверяем, может ли пользователь отвечать
        $canRespond = $poll->isUserParticipant(Auth::id()) &&
            !$poll->hasUserResponded(Auth::id()) &&
            $poll->status === 'active';

        // Проверяем, ответил ли пользователь
        $hasResponded = $poll->hasUserResponded(Auth::id());

        return view('polls.show', compact('poll', 'canRespond', 'hasResponded'));
    }

    public function respond(Request $request, Poll $poll)
    {
        $request->validate([
            'problem' => 'required|string|min:3',
            'solution' => 'required|string|min:3'
        ]);

        $user = Auth::user();

        // Проверяем, является ли пользователь участником
        if (!$poll->isUserParticipant($user->id)) {
            return back()->with('error', 'Вы не участвуете в этом опросе.');
        }

        // Проверяем, не ответил ли уже пользователь
        if ($poll->hasUserResponded($user->id)) {
            return back()->with('error', 'Вы уже ответили на этот опрос.');
        }

        // Проверяем, активен ли опрос
        if ($poll->status !== 'active') {
            return back()->with('error', 'Этот опрос уже завершен.');
        }

        DB::transaction(function () use ($request, $poll, $user) {
            // Создаем проблему
            PollProblem::create([
                'poll_id' => $poll->id,
                'user_id' => $user->id,
                'problem' => $request->problem,
                'solution' => $request->solution,
                'is_resolved' => false
            ]);

            // Отмечаем, что пользователь ответил
            $participant = PollParticipant::where('poll_id', $poll->id)
                ->where('user_id', $user->id)
                ->first();

            if ($participant) {
                $participant->markAsResponded();
            }
        });

        return redirect()->route('polls.show', $poll)
            ->with('success', 'Ваш ответ успешно сохранен!');
    }

    public function close(Poll $poll)
    {
        $this->authorize('close', $poll);

        $poll->update([
            'status' => 'closed',
            'closed_at' => now()
        ]);

        return redirect()->route('polls.show', $poll)
            ->with('success', 'Опрос закрыт.');
    }

    public function reopen(Poll $poll)
    {
        $this->authorize('close', $poll);

        $poll->update([
            'status' => 'active',
            'closed_at' => null
        ]);

        return redirect()->route('polls.show', $poll)
            ->with('success', 'Опрос переоткрыт.');
    }

    public function destroy(Poll $poll)
    {
        $this->authorize('delete', $poll);

        $poll->delete();

        return redirect()->route('polls.index')
            ->with('success', 'Опрос удален.');
    }

    public function addProblemComment(Request $request, PollProblem $problem)
    {
        $request->validate([
            'comment' => 'required|string|min:2'
        ]);

        $user = Auth::user();

        // Проверяем, участвует ли пользователь в опросе
        if (!$problem->poll->isUserParticipant($user->id)) {
            return back()->with('error', 'Вы не можете комментировать этот опрос.');
        }

        $problem->addComment($user, $request->comment);

        return back()->with('success', 'Комментарий добавлен.');
    }

    public function resolveProblem(PollProblem $problem)
    {
        $user = Auth::user();

        // Проверяем, участвует ли пользователь в опросе
        if (!$problem->poll->isUserParticipant($user->id)) {
            return back()->with('error', 'Вы не можете отмечать решение в этом опросе.');
        }

        $problem->resolve();

        return back()->with('success', 'Проблема отмечена как решенная.');
    }
}
