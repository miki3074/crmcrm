<template>
    <AuthenticatedLayout>
        <div class="app-container">
            <!-- ЛЕВАЯ ПАНЕЛЬ: Редактор -->
            <div class="editor-panel no-print">
                <div class="editor-header">
                    <h2>Конструктор законопроекта</h2>
                    <span class="badge">{{ form.articles.length }} {{ form.articles.length === 1 ? 'статья' : 'статей' }}</span>
                </div>

                <!-- Шапка документа -->
                <div class="section-block">
                    <div class="section-header">
                        <span class="section-number">1</span>
                        <h3>Шапка документа</h3>
                    </div>

                    <div class="form-group">
                        <label>Кем вносится <span class="hint">(каждая строка с новой строки)</span></label>
                        <textarea v-model="form.initiator" rows="3" class="form-textarea"></textarea>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Метка документа</label>
                            <input v-model="form.projectMark" placeholder="Проект" />
                        </div>
                        <div class="form-group">
                            <label>Вид акта</label>
                            <input v-model="form.docType" class="input-uppercase" placeholder="ФЕДЕРАЛЬНЫЙ ЗАКОН" />
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Регион / Орган</label>
                            <input v-model="form.region" placeholder="Республики Татарстан" />
                        </div>
                        <div class="form-group">
                            <label>Должность подписанта</label>
                            <input v-model="form.signerTitle" placeholder="Президент" />
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Заголовок закона</label>
                        <textarea v-model="form.title" rows="3" class="form-textarea"></textarea>
                    </div>
                </div>

                <!-- Конструктор статей -->
                <div class="section-block">
                    <div class="section-header">
                        <span class="section-number">2</span>
                        <h3>Статьи</h3>
                    </div>

                    <div class="articles-list">
                        <div
                            v-for="(article, index) in form.articles"
                            :key="index"
                            class="article-card"
                            :class="{ 'is-empty': !article.title && !article.content }"
                        >
                            <div class="article-header-row">
                                <div class="article-number-badge">Статья {{ index + 1 }}</div>
                                <button @click="removeArticle(index)" class="btn-icon delete" title="Удалить статью">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2" />
                                    </svg>
                                </button>
                            </div>

                            <div class="form-group">
                                <label>Заголовок статьи</label>
                                <input
                                    v-model="article.title"
                                    placeholder="Например: Предмет регулирования"
                                    class="article-title-input"
                                />
                            </div>

                            <div class="form-group">
                                <label>Текст статьи <span class="hint">(пустая строка = новый абзац)</span></label>
                                <textarea
                                    v-model="article.content"
                                    rows="5"
                                    placeholder="Введите текст статьи..."
                                    class="form-textarea"
                                ></textarea>
                            </div>

                            <div class="article-stats">
                                <span class="stat">{{ splitText(article.content).length }} абзацев</span>
                            </div>
                        </div>
                    </div>

                    <button @click="addArticle" class="btn-add">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14M5 12h14" />
                        </svg>
                        Добавить статью
                    </button>
                </div>

                <!-- Заключительные положения -->
                <div class="section-block">
                    <div class="section-header">
                        <span class="section-number">3</span>
                        <h3>Заключительные положения</h3>
                    </div>

                    <div class="form-group">
                        <label>ФИО подписанта (необязательно)</label>
                        <input v-model="form.signerName" placeholder="В.В. Путин" />
                    </div>
                </div>

                <!-- Кнопки действий -->
                <div class="actions">
                    <button @click="generateDocx" class="btn-action word">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 11l5 5 5-5M12 4v12" />
                        </svg>
                        Скачать Word
                    </button>
                    <button @click="printPdf" class="btn-action pdf">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M6 18h12M6 18v2a2 2 0 002 2h8a2 2 0 002-2v-2M4 9h16M4 9v6a2 2 0 002 2h12a2 2 0 002-2V9" />
                            <rect x="8" y="3" width="8" height="3" rx="1" />
                        </svg>
                        Печать / PDF
                    </button>
                </div>
            </div>

            <!-- ПРАВАЯ ПАНЕЛЬ: Предпросмотр -->
            <div class="preview-panel">
                <div class="paper-sheet">
                    <!-- Водяной знак "Проект" -->
                    <div v-if="form.projectMark" class="watermark">{{ form.projectMark }}</div>

                    <!-- Инициатор -->
                    <div class="initiator">{{ form.initiator }}</div>

                    <!-- Заголовок -->
                    <div class="doc-header">
                        <div class="doc-type">{{ form.docType }}</div>
                        <div class="doc-region">{{ form.region }}</div>
                    </div>

                    <div class="doc-title">{{ form.title }}</div>

                    <!-- Статьи -->
                    <div class="doc-body">
                        <div v-for="(article, index) in form.articles" :key="index" class="doc-article">
                            <div class="article-title">
                                Статья {{ index + 1 }}<span v-if="article.title">. {{ article.title }}</span>
                            </div>
                            <div
                                v-for="(paragraph, pIndex) in splitText(article.content)"
                                :key="pIndex"
                                class="article-paragraph"
                            >
                                {{ paragraph }}
                            </div>
                        </div>
                    </div>

                    <!-- Подпись -->
                    <div class="doc-footer">
                        <div class="signer-title">{{ form.signerTitle }}</div>
                        <div class="signer-name" v-if="form.signerName">{{ form.signerName }}</div>
                    </div>

                    <!-- Счетчик страниц (только для предпросмотра) -->
                    <div class="page-counter">Стр. 1</div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { reactive } from 'vue';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {
    Document, Packer, Paragraph, TextRun, AlignmentType,
    Header, Footer, PageNumber
} from 'docx';
import { saveAs } from 'file-saver';

// --- ДАННЫЕ ---
const form = reactive({
    initiator: 'Вносится Правительством\nРоссийской Федерации',
    projectMark: 'Проект',
    docType: 'ФЕДЕРАЛЬНЫЙ ЗАКОН',
    region: 'Республики Татарстан',
    title: 'О внесении изменений в отдельные законодательные акты',
    signerTitle: 'Президент',
    signerName: '',
    articles: [
        {
            title: 'Предмет правового регулирования',
            content: '1. Настоящий Закон регулирует отношения, возникающие в связи с...\n2. Действие настоящего Закона распространяется на...'
        },
        {
            title: '',
            content: 'Настоящий Закон вступает в силу со дня его официального опубликования.'
        }
    ]
});

// --- МЕТОДЫ РАБОТЫ СО СТАТЬЯМИ ---
const addArticle = () => {
    form.articles.push({
        title: '',
        content: ''
    });
};

const removeArticle = (index) => {
    if (confirm('Удалить эту статью?')) {
        form.articles.splice(index, 1);
    }
};

const splitText = (text) => {
    if (!text) return [];
    return text.split('\n').filter(line => line.trim() !== '');
};

// --- ГЕНЕРАЦИЯ WORD (DOCX) ---
const cmToTwip = (cm) => Math.round(cm * 566.929);

const generateDocx = () => {
    const children = [];

    // Инициатор
    const initiatorLines = form.initiator.split('\n');
    initiatorLines.forEach(line => {
        children.push(new Paragraph({
            alignment: AlignmentType.RIGHT,
            children: [new TextRun({
                text: line,
                font: "Times New Roman",
                size: 28
            })],
            spacing: { line: 240 }
        }));
    });

    // Метка
    children.push(
        new Paragraph({
            alignment: AlignmentType.RIGHT,
            children: [new TextRun({ text: form.projectMark, font: "Times New Roman", size: 28 })],
            spacing: { after: 200 }
        })
    );

    // Вид акта
    children.push(
        new Paragraph({
            alignment: AlignmentType.CENTER,
            children: [new TextRun({ text: form.docType, font: "Times New Roman", size: 28, bold: true })],
        })
    );

    // Регион
    children.push(
        new Paragraph({
            alignment: AlignmentType.CENTER,
            children: [new TextRun({ text: form.region, font: "Times New Roman", size: 28 })],
            spacing: { after: 400 }
        })
    );

    // Заголовок
    children.push(
        new Paragraph({
            alignment: AlignmentType.CENTER,
            indent: { firstLine: cmToTwip(1.25) },
            children: [new TextRun({ text: form.title, font: "Times New Roman", size: 28, bold: true })],
            spacing: { after: 400 }
        })
    );

    // Статьи
    form.articles.forEach((article, index) => {
        const articleNum = index + 1;
        let titleText = `Статья ${articleNum}`;
        if (article.title && article.title.trim() !== '') {
            titleText += `. ${article.title}`;
        }

        children.push(
            new Paragraph({
                alignment: AlignmentType.JUSTIFIED,
                indent: { firstLine: cmToTwip(1.25) },
                children: [
                    new TextRun({
                        text: titleText,
                        font: "Times New Roman",
                        size: 28,
                        bold: true
                    })
                ],
                spacing: { before: 240, after: 240 }
            })
        );

        const paragraphs = splitText(article.content);
        paragraphs.forEach(p => {
            children.push(
                new Paragraph({
                    alignment: AlignmentType.JUSTIFIED,
                    indent: { firstLine: cmToTwip(1.25) },
                    children: [new TextRun({ text: p, font: "Times New Roman", size: 28 })],
                    spacing: { line: 240 }
                })
            );
        });
    });

    // Подпись
    children.push(
        new Paragraph({ children: [], spacing: { before: 720 } })
    );

    children.push(
        new Paragraph({
            alignment: AlignmentType.LEFT,
            children: [new TextRun({ text: form.signerTitle, font: "Times New Roman", size: 28 })],
        })
    );

    if (form.signerName) {
        children.push(
            new Paragraph({
                alignment: AlignmentType.LEFT,
                children: [new TextRun({ text: form.signerName, font: "Times New Roman", size: 28 })],
            })
        );
    }

    const doc = new Document({
        styles: { default: { document: { run: { font: "Times New Roman" } } } },
        sections: [{
            properties: {
                page: {
                    margin: {
                        top: cmToTwip(2.0),
                        bottom: cmToTwip(2.0),
                        left: cmToTwip(3.0),
                        right: cmToTwip(1.0),
                    },
                },
            },
            headers: {
                default: new Header({
                    children: [
                        new Paragraph({
                            alignment: AlignmentType.RIGHT,
                            children: [
                                new TextRun({
                                    children: [PageNumber.CURRENT],
                                    font: "Times New Roman",
                                    size: 24,
                                }),
                            ],
                        }),
                    ],
                }),
            },
            children: children,
        }],
    });

    Packer.toBlob(doc).then((blob) => {
        saveAs(blob, "Законопроект.docx");
    });
};

const printPdf = () => {
    window.print();
};
</script>

<style scoped>
/* Layout */
.app-container {
    display: flex;
    height: calc(100vh - 64px);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: #f3f4f6;
}

/* Editor Panel */
.editor-panel {
    width: 520px;
    background: white;
    overflow-y: auto;
    border-right: 1px solid #e5e7eb;
    box-shadow: 4px 0 6px -2px rgba(0, 0, 0, 0.05);
    flex-shrink: 0;
}

.editor-header {
    position: sticky;
    top: 0;
    background: white;
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: space-between;
    z-index: 10;
}

.editor-header h2 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #111827;
    background: linear-gradient(135deg, #2563eb, #1e40af);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.badge {
    background: #e5e7eb;
    color: #374151;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
}

.section-block {
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}

.section-number {
    width: 28px;
    height: 28px;
    background: linear-gradient(135deg, #2563eb, #1e40af);
    color: white;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    font-weight: 600;
}

.section-header h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: #374151;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Forms */
.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.85rem;
    font-weight: 500;
    color: #4b5563;
    margin-bottom: 6px;
}

.hint {
    font-size: 0.75rem;
    font-weight: 400;
    color: #9ca3af;
}

.form-group input,
.form-group textarea {
    width: 100%;
    box-sizing: border-box;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: all 0.2s;
    background: white;
}

.form-group input:hover,
.form-group textarea:hover {
    border-color: #9ca3af;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-textarea {
    resize: vertical;
    min-height: 80px;
    font-family: 'Times New Roman', serif;
}

.input-uppercase {
    text-transform: uppercase;
}

.grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

/* Article Cards */
.articles-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 16px;
}

.article-card {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 16px;
    transition: all 0.2s;
}

.article-card:hover {
    border-color: #d1d5db;
    box-shadow: 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.article-card.is-empty {
    background: #fef2f2;
    border-color: #fee2e2;
}

.article-header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.article-number-badge {
    background: linear-gradient(135deg, #2563eb, #1e40af);
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.btn-icon {
    background: none;
    border: none;
    padding: 6px;
    cursor: pointer;
    color: #9ca3af;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.btn-icon:hover {
    background: #fee2e2;
    color: #dc2626;
}

.article-title-input {
    font-weight: 500;
}

.article-stats {
    margin-top: 8px;
    font-size: 0.75rem;
    color: #6b7280;
    text-align: right;
}

.stat {
    background: #e5e7eb;
    padding: 2px 8px;
    border-radius: 12px;
}

.btn-add {
    width: 100%;
    padding: 12px;
    background: #f3f4f6;
    color: #2563eb;
    border: 2px dashed #d1d5db;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s;
}

.btn-add:hover {
    background: #e5e7eb;
    border-color: #2563eb;
    color: #1e40af;
}

/* Action Buttons */
.actions {
    position: sticky;
    bottom: 0;
    background: white;
    padding: 20px 24px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    gap: 12px;
    box-shadow: 0 -4px 6px -2px rgba(0, 0, 0, 0.05);
}

.btn-action {
    flex: 1;
    padding: 12px 16px;
    border: none;
    border-radius: 10px;
    color: white;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s;
    font-size: 0.95rem;
}

.btn-action.word {
    background: linear-gradient(135deg, #2b579a, #1e3f72);
}

.btn-action.word:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(43, 87, 154, 0.3);
}

.btn-action.pdf {
    background: linear-gradient(135deg, #6c757d, #5a6268);
}

.btn-action.pdf:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
}

/* Preview Panel */
.preview-panel {
    flex: 1;
    background: #6b7280;
    padding: 40px;
    overflow-y: auto;
    display: flex;
    justify-content: center;
    background: linear-gradient(135deg, #4b5563 0%, #6b7280 100%);
}

/* Paper Sheet */
.paper-sheet {
    position: relative;
    background: white;
    width: 21cm;
    min-height: 29.7cm;
    padding: 2cm 1cm 2cm 3cm;
    box-sizing: border-box;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    font-family: 'Times New Roman', serif;
    font-size: 14pt;
    line-height: 1.15;
    color: #000;
    border-radius: 4px;
}

/* Watermark */
.watermark {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-45deg);
    font-size: 80pt;
    font-weight: bold;
    color: rgba(0, 0, 0, 0.03);
    white-space: nowrap;
    pointer-events: none;
    user-select: none;
    z-index: 0;
}

.initiator {
    text-align: right;
    font-size: 14pt;
    line-height: 1.4;
    white-space: pre-wrap;
    position: relative;
    z-index: 1;
}

.doc-header {
    text-align: center;
    margin: 24pt 0;
    position: relative;
    z-index: 1;
}

.doc-type {
    font-weight: bold;
    font-size: 14pt;
    text-transform: uppercase;
}

.doc-region {
    font-size: 14pt;
    margin-top: 6pt;
}

.doc-title {
    text-align: center;
    font-weight: bold;
    text-indent: 1.25cm;
    margin: 36pt 0 24pt 0;
    position: relative;
    z-index: 1;
}

.doc-article {
    margin-bottom: 18pt;
    position: relative;
    z-index: 1;
}

.article-title {
    text-align: justify;
    text-indent: 1.25cm;
    font-weight: bold;
    margin-bottom: 6pt;
}

.article-paragraph {
    text-align: justify;
    text-indent: 1.25cm;
    margin-bottom: 0;
    line-height: 1.4;
}

.doc-footer {
    margin-top: 48pt;
    position: relative;
    z-index: 1;
}

.signer-title {
    margin-bottom: 6pt;
}

.page-counter {
    position: absolute;
    top: 1cm;
    right: 1cm;
    font-size: 10pt;
    color: #6b7280;
}

/* Print Styles */
@media print {
    .editor-panel,
    .no-print,
    .actions,
    .badge,
    .watermark,
    .page-counter {
        display: none !important;
    }

    .app-container,
    .preview-panel {
        display: block;
        height: auto;
        background: white;
        padding: 0;
    }

    .paper-sheet {
        width: 100%;
        box-shadow: none;
        margin: 0;
        padding: 0;
        border-radius: 0;
    }

    @page {
        size: A4;
        margin: 2cm 1cm 2cm 3cm;
    }

    .preview-panel {
        background: white;
        padding: 0;
    }
}

/* Scrollbar Styles */
.editor-panel::-webkit-scrollbar {
    width: 8px;
}

.editor-panel::-webkit-scrollbar-track {
    background: #f3f4f6;
}

.editor-panel::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 4px;
}

.editor-panel::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}
</style>
