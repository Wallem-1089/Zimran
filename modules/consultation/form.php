<?php

declare(strict_types=1);

$consultation ??= [];
$action ??= 'save.php';
$buttonLabel ??= 'Save Consultation';
$enableWritingMode ??= false;

$fields = [
    'presenting_complaint' => 'Presenting Complaint',
    'history_of_presenting_complaint' => 'History of Presenting Complaint',
    'examination_findings' => 'Examination Findings',
    'assessment' => 'Assessment',
    'diagnosis' => 'Diagnosis',
    'treatment_plan' => 'Treatment Plan',
    'advice' => 'Advice',
    'follow_up' => 'Follow Up',
    'referral_notes' => 'Referral Notes'
];
?>

<form method="post" action="<?= e($action) ?>" class="card">
    <?= csrfField() ?>
    <input type="hidden" name="visit_id" value="<?= (int)$visit['id'] ?>">
    <?php if (!empty($consultation['id'])): ?>
        <input type="hidden" name="id" value="<?= (int)$consultation['id'] ?>">
    <?php endif; ?>

    <?php if ($enableWritingMode): ?>
        <div class="consultation-writing-toolbar">
            <div>
                <h3>Consultation Entry Mode</h3>
                <p>Type normally, or switch to writing mode for a larger clinical writing area.</p>
            </div>
            <div class="writing-mode-switch" role="group" aria-label="Consultation entry mode">
                <button type="button" class="writing-mode-option active" data-consultation-mode="type">Type</button>
                <button type="button" class="writing-mode-option" data-consultation-mode="write">Write</button>
            </div>
        </div>
    <?php endif; ?>

<?php foreach ($fields as $field => $label): ?>
        <div class="form-group consultation-writing-field">
            <label for="<?= e($field) ?>"><?= e($label) ?></label>
            <textarea
                id="<?= e($field) ?>"
                name="<?= e($field) ?>"
                class="consultation-textarea"
                data-handwriting-input="1"
                rows="<?= in_array($field, ['advice', 'follow_up', 'referral_notes'], true) ? 3 : 5 ?>"
                <?= in_array($field, ['advice', 'follow_up', 'referral_notes'], true) ? '' : 'required' ?>><?= e((string)($consultation[$field] ?? '')) ?></textarea>
            <?php if ($enableWritingMode): ?>
                <div class="consultation-handwriting-pad" hidden>
                    <div class="handwriting-pad-top">
                        <span>Write with mouse, touch, or stylus</span>
                        <span class="text-muted">Saved as handwriting for this field.</span>
                    </div>
                    <canvas class="handwriting-canvas" width="1000" height="360" aria-label="<?= e($label) ?> handwriting pad"></canvas>
                    <div class="handwriting-pad-actions">
                        <button type="button" class="btn-secondary btn-small" data-handwriting-undo>Undo Stroke</button>
                        <button type="button" class="btn-secondary btn-small" data-handwriting-clear>Clear Pad</button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><?= e($buttonLabel) ?></button>
        <a class="btn-secondary" href="<?= e(consultationBackToWorkspace((int)$visit['id'])) ?>">Cancel</a>
    </div>
</form>

<?php if ($enableWritingMode): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('form.card .consultation-writing-toolbar')?.closest('form.card');
            if (!form) {
                return;
            }

            const handwritingPrefix = <?= json_encode(consultationHandwritingPrefix(), JSON_THROW_ON_ERROR) ?>;
            const buttons = form.querySelectorAll('[data-consultation-mode]');
            const fields = form.querySelectorAll('.consultation-writing-field');
            let activeMode = 'type';

            function parseHandwriting(value) {
                if (!value || !value.startsWith(handwritingPrefix)) {
                    return null;
                }

                try {
                    const payload = JSON.parse(value.slice(handwritingPrefix.length));
                    return payload && Array.isArray(payload.strokes) ? payload : null;
                } catch (error) {
                    return null;
                }
            }

            function resizeCanvas(canvas, payload) {
                const containerWidth = Math.max(320, Math.floor(canvas.parentElement.getBoundingClientRect().width));
                const ratio = window.devicePixelRatio || 1;
                const cssHeight = Math.max(380, Math.min(620, Math.floor(containerWidth * 0.55)));

                canvas.style.width = '100%';
                canvas.style.height = cssHeight + 'px';
                canvas.width = Math.floor(containerWidth * ratio);
                canvas.height = Math.floor(cssHeight * ratio);
                canvas.dataset.logicalWidth = String(containerWidth);
                canvas.dataset.logicalHeight = String(cssHeight);

                const context = canvas.getContext('2d');
                context.setTransform(ratio, 0, 0, ratio, 0, 0);
                context.lineCap = 'round';
                context.lineJoin = 'round';
                context.lineWidth = 3.4;
                context.strokeStyle = '#0f172a';

                redrawCanvas(canvas, payload || canvas._handwritingPayload || {strokes: []});
            }

            function redrawCanvas(canvas, payload) {
                const context = canvas.getContext('2d');
                const width = Number(canvas.dataset.logicalWidth || 900);
                const height = Number(canvas.dataset.logicalHeight || 280);

                context.clearRect(0, 0, width, height);
                context.save();
                context.strokeStyle = 'rgba(37, 99, 235, 0.12)';
                context.lineWidth = 1;
                for (let y = 46; y < height; y += 40) {
                    context.beginPath();
                    context.moveTo(18, y);
                    context.lineTo(width - 18, y);
                    context.stroke();
                }
                context.restore();

                const sourceWidth = Number(payload.width || width);
                const sourceHeight = Number(payload.height || height);
                const scaleX = width / Math.max(1, sourceWidth);
                const scaleY = height / Math.max(1, sourceHeight);

                context.save();
                context.lineWidth = 3.4;
                context.strokeStyle = '#0f172a';
                context.lineCap = 'round';
                context.lineJoin = 'round';
                (payload.strokes || []).forEach(function (stroke) {
                    if (!Array.isArray(stroke) || stroke.length === 0) {
                        return;
                    }

                    context.beginPath();
                    if (stroke.length === 1) {
                        const dotX = Number(stroke[0][0]) * scaleX;
                        const dotY = Number(stroke[0][1]) * scaleY;
                        context.arc(dotX, dotY, 1.9, 0, Math.PI * 2);
                        context.fillStyle = '#0f172a';
                        context.fill();
                        return;
                    }

                    let previousX = Number(stroke[0][0]) * scaleX;
                    let previousY = Number(stroke[0][1]) * scaleY;
                    context.moveTo(previousX, previousY);

                    for (let index = 1; index < stroke.length; index += 1) {
                        const currentX = Number(stroke[index][0]) * scaleX;
                        const currentY = Number(stroke[index][1]) * scaleY;
                        const middleX = (previousX + currentX) / 2;
                        const middleY = (previousY + currentY) / 2;
                        context.quadraticCurveTo(previousX, previousY, middleX, middleY);
                        previousX = currentX;
                        previousY = currentY;
                    }

                    context.lineTo(previousX, previousY);
                    context.stroke();
                });
                context.restore();
            }

            function syncTextarea(field) {
                const textarea = field.querySelector('textarea[data-handwriting-input]');
                const canvas = field.querySelector('.handwriting-canvas');
                if (!textarea || !canvas || !canvas._handwritingPayload || activeMode !== 'write') {
                    return;
                }

                const strokes = canvas._handwritingPayload.strokes || [];
                const payload = {
                    width: Number(canvas.dataset.logicalWidth || 900),
                    height: Number(canvas.dataset.logicalHeight || 280),
                    strokes: strokes
                };

                if (strokes.length > 0) {
                    textarea.value = handwritingPrefix + JSON.stringify(payload);
                } else if (canvas._handwritingTouched) {
                    textarea.value = '';
                }
            }

            function setupPad(field) {
                const textarea = field.querySelector('textarea[data-handwriting-input]');
                const pad = field.querySelector('.consultation-handwriting-pad');
                const canvas = field.querySelector('.handwriting-canvas');
                if (!textarea || !pad || !canvas) {
                    return false;
                }

                canvas._handwritingPayload = parseHandwriting(textarea.value) || {strokes: []};
                resizeCanvas(canvas, canvas._handwritingPayload);

                let drawing = false;
                let currentStroke = null;
                let lastPoint = null;

                canvas.style.touchAction = 'none';
                canvas.style.userSelect = 'none';
                canvas.style.webkitUserSelect = 'none';

                function pointFromEvent(event) {
                    const rect = canvas.getBoundingClientRect();
                    const x = Math.max(0, Math.min(Number(canvas.dataset.logicalWidth || rect.width), event.clientX - rect.left));
                    const y = Math.max(0, Math.min(Number(canvas.dataset.logicalHeight || rect.height), event.clientY - rect.top));
                    return [Math.round(x * 10) / 10, Math.round(y * 10) / 10];
                }

                function drawSegment(from, to) {
                    if (!from || !to) {
                        return;
                    }

                    const context = canvas.getContext('2d');
                    context.save();
                    context.lineWidth = 4.2;
                    context.strokeStyle = '#0f172a';
                    context.lineCap = 'round';
                    context.lineJoin = 'round';
                    context.beginPath();
                    context.moveTo(from[0], from[1]);
                    context.quadraticCurveTo(from[0], from[1], (from[0] + to[0]) / 2, (from[1] + to[1]) / 2);
                    context.stroke();
                    context.restore();
                }

                function beginStroke(event) {
                    if (activeMode !== 'write') {
                        return;
                    }

                    event.preventDefault();
                    event.stopPropagation();
                    canvas._handwritingTouched = true;
                    if (typeof canvas.setPointerCapture === 'function' && event.pointerId !== undefined) {
                        canvas.setPointerCapture(event.pointerId);
                    }
                    document.body.classList.add('consultation-writing-in-progress');
                    drawing = true;
                    lastPoint = pointFromEvent(event);
                    currentStroke = [lastPoint];
                    canvas._handwritingPayload.strokes.push(currentStroke);
                    drawSegment([lastPoint[0] - 0.1, lastPoint[1] - 0.1], lastPoint);
                    syncTextarea(field);
                }

                function continueStroke(event) {
                    if (!drawing || !currentStroke) {
                        return;
                    }

                    event.preventDefault();
                    event.stopPropagation();
                    const point = pointFromEvent(event);
                    const previous = currentStroke[currentStroke.length - 1];
                    const dx = point[0] - previous[0];
                    const dy = point[1] - previous[1];
                    if (Math.sqrt(dx * dx + dy * dy) < 0.8) {
                        return;
                    }

                    currentStroke.push(point);
                    drawSegment(lastPoint || previous, point);
                    lastPoint = point;
                    syncTextarea(field);
                }

                function endStroke(event) {
                    if (event) {
                        event.preventDefault();
                        event.stopPropagation();
                        if (typeof canvas.releasePointerCapture === 'function' && event.pointerId !== undefined) {
                            try {
                                canvas.releasePointerCapture(event.pointerId);
                            } catch (error) {
                                // Pointer may already be released by the browser.
                            }
                        }
                    }

                    drawing = false;
                    currentStroke = null;
                    lastPoint = null;
                    document.body.classList.remove('consultation-writing-in-progress');
                    redrawCanvas(canvas, canvas._handwritingPayload);
                    syncTextarea(field);
                }

                canvas.addEventListener('pointerdown', beginStroke, {passive: false});
                canvas.addEventListener('pointermove', continueStroke, {passive: false});
                ['pointerup', 'pointercancel', 'lostpointercapture'].forEach(function (eventName) {
                    canvas.addEventListener(eventName, endStroke, {passive: false});
                });

                canvas.addEventListener('touchstart', function (event) {
                    if (activeMode === 'write') {
                        event.preventDefault();
                    }
                }, {passive: false});

                canvas.addEventListener('touchmove', function (event) {
                    if (activeMode === 'write') {
                        event.preventDefault();
                    }
                }, {passive: false});

                field.querySelector('[data-handwriting-undo]')?.addEventListener('click', function () {
                    canvas._handwritingTouched = true;
                    canvas._handwritingPayload.strokes.pop();
                    redrawCanvas(canvas, canvas._handwritingPayload);
                    syncTextarea(field);
                });

                field.querySelector('[data-handwriting-clear]')?.addEventListener('click', function () {
                    canvas._handwritingTouched = true;
                    canvas._handwritingPayload.strokes = [];
                    redrawCanvas(canvas, canvas._handwritingPayload);
                    syncTextarea(field);
                });

                return canvas._handwritingPayload.strokes.length > 0;
            }

            let hasHandwriting = false;
            fields.forEach(function (field) {
                hasHandwriting = setupPad(field) || hasHandwriting;
            });

            function setMode(mode) {
                activeMode = mode;
                const writing = mode === 'write';

                buttons.forEach(function (item) {
                    item.classList.toggle('active', item.dataset.consultationMode === mode);
                });

                fields.forEach(function (field) {
                    const pad = field.querySelector('.consultation-handwriting-pad');
                    const textarea = field.querySelector('textarea[data-handwriting-input]');
                    field.classList.toggle('writing-pad-active', writing);
                    if (pad) {
                        pad.hidden = !writing;
                    }
                    if (textarea) {
                        if (writing && textarea.required) {
                            textarea.dataset.wasRequired = '1';
                            textarea.required = false;
                        } else if (!writing && textarea.dataset.wasRequired === '1') {
                            textarea.required = true;
                        }
                        if (writing && !textarea.value.startsWith(handwritingPrefix)) {
                            textarea.dataset.typedValue = textarea.value;
                        }
                        if (!writing && textarea.value.startsWith(handwritingPrefix)) {
                            textarea.value = textarea.dataset.typedValue || '';
                        }
                        textarea.hidden = writing;
                    }
                    if (writing) {
                        const canvas = field.querySelector('.handwriting-canvas');
                        if (canvas) {
                            resizeCanvas(canvas);
                        }
                        syncTextarea(field);
                    }
                });
            }

            buttons.forEach(function (button) {
                button.addEventListener('click', function () {
                    setMode(button.dataset.consultationMode || 'type');
                });
            });

            window.addEventListener('resize', function () {
                if (activeMode === 'write') {
                    fields.forEach(function (field) {
                        const canvas = field.querySelector('.handwriting-canvas');
                        if (canvas) {
                            resizeCanvas(canvas);
                        }
                    });
                }
            });

            form.addEventListener('submit', function () {
                if (activeMode === 'write') {
                    fields.forEach(syncTextarea);
                }
            });

            if (hasHandwriting) {
                setMode('write');
            }
        });
    </script>
<?php endif; ?>
