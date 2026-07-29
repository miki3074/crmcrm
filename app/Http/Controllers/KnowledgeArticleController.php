<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeFolder;
use App\Services\KnowledgeFolderAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class KnowledgeArticleController extends Controller
{
    public function create(
    Request $request,
    Company $company,
    KnowledgeFolder $folder,
    KnowledgeFolderAccessService $folderAccess
): Response {
    $this->ensureFolderBelongsToCompany(
        $company,
        $folder
    );

    $userId = $request->user()->id;

    abort_unless(
        $folderAccess->canCreateContent(
            $userId,
            $folder
        ),
        403,
        'У вас нет права создавать статьи в этой папке.'
    );

    return Inertia::render('Knowledge/Articles/Create', [
        'company' => [
            'id' => $company->id,
            'name' => $company->name,
            'logo' => $company->logo,
        ],

        'folder' => [
            'id' => $folder->id,
            'name' => $folder->name,
            'parent_id' => $folder->parent_id,
        ],

        'article' => [
            'title' => '',
            'content' => [
                'type' => 'doc',
                'content' => [],
            ],
            'content_text' => '',
            'status' => KnowledgeArticle::STATUS_DRAFT,
        ],
    ]);
}

    public function store(
        Request $request,
        Company $company,
        KnowledgeFolder $folder,
        KnowledgeFolderAccessService $folderAccess
    ): RedirectResponse {
        $this->ensureFolderBelongsToCompany(
            $company,
            $folder
        );

        $userId = $request->user()->id;

        abort_unless(
            $folderAccess->canCreateContent(
                $userId,
                $folder
            ),
            403,
            'У вас нет права создавать статьи в этой папке.'
        );

        $validated = $request->validate([
            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'content' => [
                'required',
                'array',
            ],

            'content.type' => [
                'required',
                'string',
                Rule::in(['doc']),
            ],

            'content_text' => [
                'nullable',
                'string',
            ],

            'status' => [
                'required',
                Rule::in([
                    KnowledgeArticle::STATUS_DRAFT,
                    KnowledgeArticle::STATUS_PUBLISHED,
                ]),
            ],
        ]);

        $article = DB::transaction(function () use (
            $validated,
            $company,
            $folder,
            $userId
        ) {
            $position = KnowledgeArticle::query()
                ->where('folder_id', $folder->id)
                ->max('position');

            return KnowledgeArticle::create([
                'company_id' => $company->id,
                'folder_id' => $folder->id,
                'created_by' => $userId,
                'updated_by' => $userId,
                'title' => trim($validated['title']),
                'content' => $validated['content'],
                'content_text' => trim(
                    $validated['content_text'] ?? ''
                ),
                'status' => $validated['status'],
                'position' => ($position ?? -1) + 1,
            ]);
        });

        return redirect()
            ->route('knowledge.articles.edit', [
                'company' => $company->id,
                'article' => $article->id,
            ])
            ->with('success', 'Статья создана.');
    }

    public function show(
        Request $request,
        Company $company,
        KnowledgeArticle $article,
        KnowledgeFolderAccessService $folderAccess
    ): Response {
        $this->ensureArticleBelongsToCompany(
            $company,
            $article
        );

        $article->load([
            'folder',
            'creator:id,name',
            'updater:id,name',
            'files',
        ]);

        $userId = $request->user()->id;

        abort_unless(
            $folderAccess->canView(
                $userId,
                $article->folder
            ),
            403,
            'У вас нет доступа к этой статье.'
        );

        return Inertia::render('Knowledge/Articles/Show', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'logo' => $company->logo,
            ],

            'folder' => [
                'id' => $article->folder->id,
                'name' => $article->folder->name,
            ],

            'article' => [
                'id' => $article->id,
                'title' => $article->title,
                'content' => $article->content,
                'content_text' => $article->content_text,
                'status' => $article->status,
                'creator' => $article->creator,
                'updater' => $article->updater,
                'files' => $article->files,
                'created_at' => optional(
                    $article->created_at
                )->toISOString(),
                'updated_at' => optional(
                    $article->updated_at
                )->toISOString(),
            ],

            'permissions' => [
                'edit' => $folderAccess->canEditContent(
                    $userId,
                    $article->folder
                ),
                'delete' => $folderAccess->canDeleteContent(
                    $userId,
                    $article->folder
                 ),
            ],
        ]);
    }

    public function edit(
    Request $request,
    Company $company,
    KnowledgeArticle $article,
    KnowledgeFolderAccessService $folderAccess
): Response {
    $this->ensureArticleBelongsToCompany(
        $company,
        $article
    );

    $article->load([
        'folder',
        'files',
    ]);

    $userId = $request->user()->id;

    abort_unless(
        $folderAccess->canEditContent(
            $userId,
            $article->folder
        ),
        403,
        'У вас нет права редактировать эту статью.'
    );

    return Inertia::render('Knowledge/Articles/Edit', [
        'company' => [
            'id' => $company->id,
            'name' => $company->name,
            'logo' => $company->logo,
        ],

        'folder' => [
            'id' => $article->folder->id,
            'name' => $article->folder->name,
        ],

        'article' => [
            'id' => $article->id,
            'title' => $article->title,
            'content' => $article->content,
            'content_text' => $article->content_text,
            'status' => $article->status,
            'files' => $article->files,
        ],

        'permissions' => [
            'delete' => $folderAccess->canDeleteContent(
                $userId,
                $article->folder
            ),
        ],
    ]);
}

    public function update(
        Request $request,
        Company $company,
        KnowledgeArticle $article,
        KnowledgeFolderAccessService $folderAccess
    ): RedirectResponse {
        $this->ensureArticleBelongsToCompany(
            $company,
            $article
        );

        $article->loadMissing('folder');

        $userId = $request->user()->id;

        abort_unless(
            $folderAccess->canEditContent(
                $userId,
                $article->folder
            ),
            403,
            'У вас нет права редактировать эту статью.'
        );

        $validated = $request->validate([
            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'content' => [
                'required',
                'array',
            ],

            'content.type' => [
                'required',
                'string',
                Rule::in(['doc']),
            ],

            'content_text' => [
                'nullable',
                'string',
            ],

            'status' => [
                'required',
                Rule::in([
                    KnowledgeArticle::STATUS_DRAFT,
                    KnowledgeArticle::STATUS_PUBLISHED,
                ]),
            ],
        ]);

        $article->update([
            'updated_by' => $userId,
            'title' => trim($validated['title']),
            'content' => $validated['content'],
            'content_text' => trim(
                $validated['content_text'] ?? ''
            ),
            'status' => $validated['status'],
        ]);

        return back()->with(
            'success',
            'Статья сохранена.'
        );
    }

    private function ensureFolderBelongsToCompany(
        Company $company,
        KnowledgeFolder $folder
    ): void {
        abort_unless(
            (int) $folder->company_id === (int) $company->id,
            404
        );
    }

    private function ensureArticleBelongsToCompany(
        Company $company,
        KnowledgeArticle $article
    ): void {
        abort_unless(
            (int) $article->company_id === (int) $company->id,
            404
        );
    }

    public function destroy(
    Request $request,
    Company $company,
    KnowledgeArticle $article,
    KnowledgeFolderAccessService $folderAccess
): RedirectResponse {
    $this->ensureArticleBelongsToCompany(
        $company,
        $article
    );

    $article->loadMissing([
        'folder',
        'files',
    ]);

    $userId = $request->user()->id;

    abort_unless(
        $folderAccess->canDeleteContent(
            $userId,
            $article->folder
        ),
        403,
        'У вас нет права удалять эту статью.'
    );

    $folderId = $article->folder_id;

    DB::transaction(function () use ($article) {
        foreach ($article->files as $file) {
            if (!empty($file->path)) {
                Storage::disk('local')->delete(
                    $file->path
                );
            }

            $file->delete();
        }

        $article->delete();
    });

    return redirect()
        ->route('knowledge.folders.show', [
            'company' => $company->id,
            'folder' => $folderId,
        ])
        ->with('success', 'Статья удалена.');
}
}