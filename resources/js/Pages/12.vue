<template>
    <div class="app-container">
        <div class="controls">
            <h2>Конструктор Законопроекта (ФЗ)</h2>

            <div class="form-group">
                <label>Кем вносится (в правом верхнем углу):</label>
                <textarea v-model="form.initiator" rows="2"></textarea>
            </div>

            <div class="form-group">
                <label>Вид документа:</label>
                <input v-model="form.docType" type="text" />
            </div>

            <div class="form-group">
                <label>Наименование закона:</label>
                <textarea v-model="form.title" rows="2"></textarea>
            </div>

            <div class="form-group">
                <label>Текст закона (с главами и статьями):</label>
                <textarea v-model="form.body" rows="15" placeholder="Глава 1. Общие положения..."></textarea>
                <small>Для разделения абзацев используйте Enter.</small>
            </div>

            <div class="form-group">
                <label>Подписант (внизу слева):</label>
                <input v-model="form.signer" type="text" />
            </div>

            <div class="buttons">
                <button @click="generateDocx" class="btn btn-word">Скачать Word (.docx)</button>
                <button @click="printPdf" class="btn btn-pdf">Печать / PDF</button>
            </div>
        </div>

        <!-- Визуальное превью (имитация листа А4) -->
        <div class="preview-container">
            <div id="print-area" class="a4-sheet">
                <div class="initiator">{{ form.initiator }}</div>

                <!-- Отступ 24pt -->
                <div class="spacer-24pt"></div>

                <div class="project-mark">Проект</div>

                <!-- Отступ 42pt -->
                <div class="spacer-42pt"></div>

                <div class="doc-type">{{ form.docType }}</div>

                <!-- Отступ 38pt -->
                <div class="spacer-38pt"></div>

                <div class="doc-title">{{ form.title }}</div>

                <!-- Отступ 24pt -->
                <div class="spacer-24pt"></div>

                <div class="doc-body">
                    <p v-for="(par, idx) in splitBody" :key="idx">{{ par }}</p>
                </div>

                <!-- Отступ 36pt -->
                <div class="spacer-36pt"></div>

                <div class="signer">{{ form.signer }}</div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, computed } from 'vue';
import {
    Document, Packer, Paragraph, TextRun, AlignmentType,
    Header, Footer, PageNumber
} from 'docx';
import { saveAs } from 'file-saver';

// Данные формы по умолчанию
const form = reactive({
    initiator: 'Вносится Правительством\nРоссийской Федерации',
    docType: 'ФЕДЕРАЛЬНЫЙ ЗАКОН',
    title: 'Об архивном деле в Российской Федерации',
    body: `Глава 1. Общие положения

Статья 1. Предмет регулирования

1. Настоящий Федеральный закон регулирует отношения в сфере организации хранения, комплектования, учета и использования документов Архивного фонда Российской Федерации и других архивных документов...`,
    signer: 'Президент Российской Федерации'
});

// Разбивка текста на параграфы для превью
const splitBody = computed(() => {
    return form.body.split('\n').filter(line => line.trim() !== '');
});

// === ЛОГИКА WORD (DOCX) ===

// Конвертеры единиц измерения
// 1 см = 567 твипов
const cmToTwip = (cm) => Math.round(cm * 566.929);
// 1 пункт (pt) = 20 твипов
const ptToTwip = (pt) => pt * 20;

const generateDocx = () => {
    const children = [];

    // 1. "Вносится..." (Правый верхний угол)
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

    // 2. Отступ 24 пт (пустой параграф)
    children.push(new Paragraph({
        children: [],
        spacing: { after: ptToTwip(24) }
    }));

    // 3. "Проект" (Справа, шрифт 22 пт)
    children.push(new Paragraph({
        alignment: AlignmentType.RIGHT,
        children: [new TextRun({
            text: "Проект",
            font: "Times New Roman",
            size: 44 // 22pt * 2 = 44 half-points
        })],
        // Отступ после 42 пт
        spacing: { after: ptToTwip(42) }
    }));

    // 4. "ФЕДЕРАЛЬНЫЙ ЗАКОН" (Центр, Жирный, обычно 14-16 пт, возьмем 14 пт)
    children.push(new Paragraph({
        alignment: AlignmentType.CENTER,
        children: [new TextRun({
            text: form.docType,
            font: "Times New Roman",
            bold: true,
            allCaps: true,
            size: 28
        })],
        // Отступ после 38 пт
        spacing: { after: ptToTwip(38) }
    }));

    // 5. Название закона (Центр, Жирный, шрифт №15 по схеме = 30 half-points)
    children.push(new Paragraph({
        alignment: AlignmentType.CENTER,
        children: [new TextRun({
            text: form.title,
            font: "Times New Roman",
            bold: true,
            size: 30 // 15pt
        })],
        // Отступ после 24 пт
        spacing: { after: ptToTwip(24) }
    }));

    // 6. Текст закона
    const paragraphs = form.body.split('\n');
    paragraphs.forEach(pText => {
        if (pText.trim()) {
            children.push(new Paragraph({
                alignment: AlignmentType.JUSTIFIED, // Выравнивание по ширине
                indent: { firstLine: cmToTwip(1.25) }, // Красная строка 1.25 см
                children: [new TextRun({
                    text: pText.trim(),
                    font: "Times New Roman",
                    size: 28 // 14pt
                })],
                // Межстрочный интервал. В схеме указано "Двойной" (Double).
                // Одинарный = 240, 1.5 = 360, Двойной = 480.
                spacing: { line: 480 }
            }));
        }
    });

    // 7. Отступ перед подписью 36 пт
    children.push(new Paragraph({
        children: [],
        spacing: { after: ptToTwip(36) }
    }));

    // 8. Подпись
    children.push(new Paragraph({
        alignment: AlignmentType.LEFT,
        children: [new TextRun({
            text: form.signer,
            font: "Times New Roman",
            size: 28
        })]
    }));

    // Создание документа с полями
    const doc = new Document({
        styles: {
            default: {
                document: {
                    run: {
                        font: "Times New Roman",
                    },
                },
            },
        },
        sections: [{
            properties: {
                page: {
                    margin: {
                        top: cmToTwip(2.5),    // 2.5 см
                        bottom: cmToTwip(2.0), // 2.0 см
                        left: cmToTwip(3.0),   // 3.0 см
                        right: cmToTwip(1.5),  // 1.5 см
                    },
                },
            },
            children: children,
        }],
    });

    Packer.toBlob(doc).then(blob => {
        saveAs(blob, "Законопроект.docx");
    });
};

// === ЛОГИКА PDF / ПЕЧАТЬ ===
const printPdf = () => {
    window.print();
};
</script>

<style>
/* Общие стили приложения */
.app-container {
    display: flex;
    gap: 20px;
    padding: 20px;
    font-family: Arial, sans-serif;
    background: #f0f2f5;
}

.controls {
    flex: 1;
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.form-group { margin-bottom: 15px; }
.form-group label { display: block; font-weight: bold; margin-bottom: 5px; }
.form-group textarea, .form-group input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }

.buttons { display: flex; gap: 10px; margin-top: 20px; }
.btn { padding: 10px 20px; border: none; cursor: pointer; color: white; border-radius: 4px; font-size: 16px; }
.btn-word { background: #2b579a; }
.btn-pdf { background: #d32f2f; }

/* === ВИЗУАЛИЗАЦИЯ ЛИСТА А4 (CSS PREVIEW) === */
.preview-container {
    flex: 1;
    display: flex;
    justify-content: center;
    background: #525659;
    padding: 20px;
    overflow-y: auto;
    max-height: 90vh;
}

.a4-sheet {
    background: white;
    width: 210mm;
    min-height: 297mm;
    /* Поля согласно схеме: Верх 2.5, Право 1.5, Низ 2.0, Лево 3.0 */
    padding: 25mm 15mm 20mm 30mm;
    box-shadow: 0 0 10px rgba(0,0,0,0.5);
    font-family: 'Times New Roman', serif;
    box-sizing: border-box;
}

/* Типографика превью */
.initiator { text-align: right; font-size: 14pt; line-height: 1; white-space: pre-wrap; }
.project-mark { text-align: right; font-size: 22pt; }
.doc-type { text-align: center; font-size: 14pt; font-weight: bold; text-transform: uppercase; }
.doc-title { text-align: center; font-size: 15pt; font-weight: bold; } /* N 15 в схеме */
.doc-body { text-align: justify; font-size: 14pt; line-height: 2; } /* Двойной интервал */
.doc-body p { text-indent: 1.25cm; margin: 0; }
.signer { text-align: left; font-size: 14pt; }

/* Визуальные отступы (конвертация pt -> px для экрана примерно) */
/* 1pt ≈ 1.33px */
.spacer-24pt { height: 32px; }
.spacer-42pt { height: 56px; }
.spacer-38pt { height: 50px; }
.spacer-36pt { height: 48px; }

/* === СТИЛИ ДЛЯ ПЕЧАТИ (PDF) === */
@media print {
    .controls, .buttons { display: none; }
    .app-container, .preview-container { padding: 0; background: white; display: block; }
    .a4-sheet {
        width: 100%;
        box-shadow: none;
        margin: 0;
        /* При печати браузер сам добавляет поля, поэтому здесь сбрасываем или настраиваем */
        padding: 0;
    }

    /* Указываем браузеру использовать настройки страницы */
    @page {
        size: A4;
        margin-top: 2.5cm;
        margin-bottom: 2.0cm;
        margin-left: 3.0cm;
        margin-right: 1.5cm;
    }
}
</style>
