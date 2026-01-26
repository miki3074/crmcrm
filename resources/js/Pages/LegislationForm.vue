<template>
    <AuthenticatedLayout>
    <div class="app-container">
        <!-- ЛЕВАЯ ПАНЕЛЬ: Редактор -->
        <div class="editor-panel no-print">
            <h2>Конструктор Законопроекта</h2>

            <!-- Шапка документа -->
            <div class="section-block">


                <h3>1. Шапка документа</h3>
                <div class="form-group">
                    <label>Кем вносится:</label>
                    <textarea v-model="form.initiator" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label>Метка (справа сверху):</label>
                    <input v-model="form.projectMark" />
                </div>
                <div class="form-group">
                    <label>Вид акта:</label>
                    <input v-model="form.docType" class="input-uppercase" />
                </div>
                <div class="form-group">
                    <label>Регион / Орган:</label>
                    <input v-model="form.region" />
                </div>
                <div class="form-group">
                    <label>Заголовок закона:</label>
                    <textarea v-model="form.title" rows="3"></textarea>
                </div>
            </div>

            <!-- Конструктор статей -->
            <div class="section-block">
                <h3>2. Статьи</h3>
                <div class="articles-list">
                    <div
                        v-for="(article, index) in form.articles"
                        :key="index"
                        class="article-card"
                    >
                        <div class="article-header-row">
                            <span class="article-number">Статья {{ index + 1 }}</span>
                            <button @click="removeArticle(index)" class="btn-icon delete" title="Удалить">✕</button>
                        </div>

                        <div class="form-group">
                            <input
                                v-model="article.title"
                                placeholder="Заголовок статьи (например: Предмет регулирования)"
                                class="article-title-input"
                            />
                        </div>

                        <div class="form-group">
              <textarea
                  v-model="article.content"
                  rows="5"
                  placeholder="Текст статьи. Разделяйте части пустой строкой."
              ></textarea>
                        </div>
                    </div>
                </div>

                <button @click="addArticle" class="btn-add">
                    + Добавить статью
                </button>
            </div>

            <!-- Подвал -->
            <div class="section-block">
                <h3>3. Заключительные положения</h3>
                <div class="form-group">
                    <label>Должность подписанта:</label>
                    <input v-model="form.signerTitle" />
                </div>
                <div class="form-group">
                    <label>ФИО (необязательно):</label>
                    <input v-model="form.signerName" />
                </div>
            </div>

            <!-- Кнопки действий -->
            <div class="actions">
                <button @click="generateDocx" class="btn-action word">Скачать Word (.docx)</button>
                <button @click="printPdf" class="btn-action pdf">Печать / PDF</button>
            </div>
        </div>

        <!-- ПРАВАЯ ПАНЕЛЬ: Предпросмотр -->
        <div class="preview-panel">

            <div class="paper-sheet">

                <div class="initiator">{{ form.initiator }}</div>
                <div class="spacer-24pt"></div>
                <!-- Метка Проект -->
                <div class="doc-mark">{{ form.projectMark }}</div>

                <!-- Заголовок -->
                <div class="doc-header">
                    <div class="doc-type">{{ form.docType }}</div>
                    <div class="doc-region">{{ form.region }}</div>
                </div>

                <div class="doc-title">{{ form.title }}</div>

                <!-- Генерация статей -->
                <div class="doc-body">
                    <div v-for="(article, index) in form.articles" :key="index" class="doc-article">
                        <!-- Заголовок статьи -->
                        <div class="article-title">
                            Статья {{ index + 1 }}<span v-if="article.title">. {{ article.title }}</span>
                        </div>
                        <!-- Текст статьи (разбиваем на абзацы) -->
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
                    <div class="signer-name">{{ form.signerName }}</div>
                </div>
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
    region: 'Республики  Татарстан',
    title: 'О внесении изменений в отдельные законодательные акты',
    signerTitle: 'Президент',
    signerName: '',
    // Массив статей
    articles: [
        {
            title: 'Предмет правового регулирования',
            content: '1. Настоящий Закон регулирует отношения, возникающие в связи с...\n2. Действие настоящего Закона распространяется на...'
        },
        {
            title: '', // Статья без заголовка
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
const cmToTwip = (cm) => Math.round(cm * 566.929); // 1 cm = 567 twips

const generateDocx = () => {
    const children = [];

    const initiatorLines = form.initiator.split('\n');
    initiatorLines.forEach(line => {
        children.push(new Paragraph({
            alignment: AlignmentType.RIGHT,
            children: [new TextRun({
                text: line,
                font: "Times New Roman",
                size: 28 // 14pt (28 half-points)
            })],
            spacing: { line: 240 } // Одинарный интервал
        }));
    });

    // 1. Метка "Проект"
    children.push(
        new Paragraph({
            alignment: AlignmentType.RIGHT,
            children: [new TextRun({ text: form.projectMark, font: "Times New Roman", size: 28 })], // 14pt
            spacing: { after: 200 }
        })
    );

    // 2. ЗАКОН (14pt, Жирный, Центр)
    children.push(
        new Paragraph({
            alignment: AlignmentType.CENTER,
            children: [new TextRun({ text: form.docType, font: "Times New Roman", size: 28, bold: true })],
        })
    );

    // 3. Регион (14pt, Обычный, Центр)
    children.push(
        new Paragraph({
            alignment: AlignmentType.CENTER,
            children: [new TextRun({ text: form.region, font: "Times New Roman", size: 28 })],
            spacing: { after: 400 } // Отступ 2 интервала
        })
    );

    // 4. Название закона (Жирный, Центр)
    children.push(
        new Paragraph({
            alignment: AlignmentType.CENTER,
            indent: { firstLine: cmToTwip(1.25) }, // Красная строка даже у заголовка по требованиям
            children: [new TextRun({ text: form.title, font: "Times New Roman", size: 28, bold: true })],
            spacing: { after: 400 } // Отступ перед текстом
        })
    );

    // 5. ГЕНЕРАЦИЯ СТАТЕЙ
    form.articles.forEach((article, index) => {
        const articleNum = index + 1;

        // Формируем заголовок: "Статья X. Название" или просто "Статья X"
        let titleText = `Статья ${articleNum}`;
        if (article.title && article.title.trim() !== '') {
            titleText += `. ${article.title}`;
        }

        // Параграф заголовка статьи
        children.push(
            new Paragraph({
                alignment: AlignmentType.JUSTIFIED,
                indent: { firstLine: cmToTwip(1.25) },
                children: [
                    new TextRun({
                        text: titleText,
                        font: "Times New Roman",
                        size: 28, // 14pt
                        bold: true // Жирный по требованиям
                    })
                ],
                spacing: { before: 240, after: 240 } // Отбивка сверху и снизу
            })
        );

        // Параграфы текста статьи
        const paragraphs = splitText(article.content);
        paragraphs.forEach(p => {
            children.push(
                new Paragraph({
                    alignment: AlignmentType.JUSTIFIED,
                    indent: { firstLine: cmToTwip(1.25) },
                    children: [new TextRun({ text: p, font: "Times New Roman", size: 28 })],
                    spacing: { line: 240 } // Одинарный интервал
                })
            );
        });
    });

    // 6. Подпись (отступ 3 интервала)
    children.push(
        new Paragraph({
            children: [],
            spacing: { before: 720 }
        })
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

    // Сборка документа
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
            // Нумерация страниц (справа сверху)
            headers: {
                default: new Header({
                    children: [
                        new Paragraph({
                            alignment: AlignmentType.RIGHT,
                            children: [
                                new TextRun({
                                    children: [PageNumber.CURRENT],
                                    font: "Times New Roman",
                                    size: 24, // 12pt для номеров страниц
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

// --- ПЕЧАТЬ / PDF ---
const printPdf = () => {
    window.print();
};
</script>

<style scoped>

.spacer-24pt { height: 32px; }
.initiator { text-align: right; font-size: 14pt; line-height: 1; white-space: pre-wrap; }
/* Layout */
.app-container {
    display: flex;
    height: 100vh;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    background: #e9ecef;
}

/* Editor Panel */
.editor-panel {
    width: 450px;
    background: white;
    padding: 20px;
    overflow-y: auto;
    border-right: 1px solid #ccc;
    box-shadow: 2px 0 5px rgba(0,0,0,0.05);
    flex-shrink: 0;
}

.section-block {
    margin-bottom: 25px;
    border-bottom: 1px solid #eee;
    padding-bottom: 15px;
}

h2 { margin-top: 0; font-size: 1.2rem; color: #333; }
h3 { font-size: 1rem; color: #666; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 0.5px; }

.form-group { margin-bottom: 12px; }
.form-group label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px; color: #444; }
.form-group input, .form-group textarea {
    width: 100%; box-sizing: border-box; padding: 8px;
    border: 1px solid #ced4da; border-radius: 4px; font-size: 14px;
}
.form-group textarea { resize: vertical; }
.input-uppercase { text-transform: uppercase; }

/* Article Cards */
.article-card {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 10px;
    margin-bottom: 10px;
    transition: all 0.2s;
}
.article-card:hover { border-color: #adb5bd; }

.article-header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}
.article-number { font-weight: bold; font-size: 0.9rem; color: #0056b3; }

.article-title-input { font-weight: bold; }

.btn-icon {
    background: none; border: none; font-size: 1.1rem; cursor: pointer; color: #999;
}
.btn-icon.delete:hover { color: #dc3545; }

.btn-add {
    width: 100%;
    padding: 10px;
    background: #e7f1ff;
    color: #0d6efd;
    border: 1px dashed #0d6efd;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
}
.btn-add:hover { background: #cfe2ff; }

/* Action Buttons */
.actions { display: flex; gap: 10px; margin-top: 20px; }
.btn-action {
    flex: 1; padding: 12px; border: none; border-radius: 4px;
    color: white; font-weight: bold; cursor: pointer;
}
.word { background: #2b579a; }
.word:hover { background: #1e3f72; }
.pdf { background: #6c757d; }
.pdf:hover { background: #5a6268; }

/* Preview Panel */
.preview-panel {
    flex: 1;
    background: #525659;
    padding: 40px;
    overflow-y: auto;
    display: flex;
    justify-content: center;
}

/* Paper Sheet (A4 Simulation) */
.paper-sheet {
    background: white;
    width: 21cm; /* A4 width */
    min-height: 29.7cm; /* A4 height */
    /* ГОСТ Поля: Левое 3, Правое 1, Верх/Низ 2 */
    padding: 2cm 1cm 2cm 3cm;
    box-sizing: border-box;
    box-shadow: 0 0 15px rgba(0,0,0,0.3);

    /* Typography */
    font-family: 'Times New Roman', serif;
    font-size: 14pt; /* Основной шрифт №14 */
    line-height: 1.15; /* Одинарный/множитель */
    color: black;
}

/* Preview Styles matching GOST */
.doc-mark { text-align: right; margin-bottom: 20px; }
.doc-header { text-align: center; margin-bottom: 20px; }
.doc-type { font-weight: bold; font-size: 14pt; text-transform: uppercase; }
.doc-region { font-size: 14pt; margin-top: 5px; }

.doc-title {
    text-align: center;
    font-weight: bold;
    text-indent: 1.25cm; /* Красная строка для заголовка тоже */
    margin-bottom: 25px;
}

.doc-article { margin-bottom: 15px; }

.article-title {
    text-align: justify;
    text-indent: 1.25cm;
    font-weight: bold;
    margin-bottom: 5px;
}

.article-paragraph {
    text-align: justify;
    text-indent: 1.25cm; /* Красная строка 1.25 */
    margin-bottom: 0; /* В ворде абзацы плотно, если не задано иное */
}

.doc-footer { margin-top: 50px; }
.signer-title { margin-bottom: 5px; }

/* Print Styles */
@media print {
    .editor-panel, .no-print { display: none; }
    .app-container, .preview-panel { display: block; height: auto; background: white; padding: 0; }
    .paper-sheet {
        width: 100%;
        box-shadow: none;
        margin: 0;
        padding: 0; /* Браузер сам сделает отступы через @page */
    }
    @page {
        size: A4;
        margin-top: 2cm;
        margin-bottom: 2cm;
        margin-left: 3cm;
        margin-right: 1cm;
    }
}
</style>
