<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Hospital Management System
| Global Helper Functions
|--------------------------------------------------------------------------
*/

/**
 * Escape HTML output.
 *
 * @param mixed $value
 * @return string
 */
function e($value): string
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

/**
 * Resolve hospital/application branding for UI layouts.
 */
function appBranding(?PDO $pdo = null): array
{
    static $cache = [];

    $cacheKey = $pdo ? (string)spl_object_id($pdo) : 'config';
    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }

    $config = require __DIR__ . '/app.php';

    $branding = [
        'hospital_name' => (string)($config['hospital']['name'] ?? 'Zimran'),
        'hospital_code' => (string)($config['hospital']['code'] ?? 'Zimran'),
        'product_name' => (string)($config['app']['name'] ?? 'E-HMIS'),
    ];

    if ($pdo instanceof PDO) {
        try {
            require_once __DIR__ . '/../services/SettingsService.php';
            $settings = new SettingsService($pdo);
            $branding['hospital_name'] = (string)$settings->get(
                'hospital.name',
                $branding['hospital_name']
            );
            $branding['hospital_code'] = (string)$settings->get(
                'hospital.code',
                $branding['hospital_code']
            );
            $branding['product_name'] = (string)$settings->get(
                'app.product_name',
                $branding['product_name']
            );
        } catch (Throwable) {
            // Fall back to config branding when settings are unavailable.
        }
    }

    $branding['display_name'] = trim($branding['hospital_name']) !== ''
        ? $branding['hospital_name']
        : $branding['product_name'];

    $branding['full_name'] = trim($branding['product_name']) !== ''
        ? trim($branding['display_name'] . ' ' . $branding['product_name'])
        : $branding['display_name'];

    return $cache[$cacheKey] = $branding;
}

/**
 * Redirect to another page.
 *
 * @param string $url
 * @return never
 */
function redirect(string $url): never
{
    header("Location: {$url}");
    exit;
}

/**
 * Check if a value is present.
 *
 * @param mixed $value
 * @return bool
 */
function filled($value): bool
{
    return isset($value)
        && trim((string)$value) !== '';
}

/**
 * Format date.
 *
 * @param string|null $date
 * @return string
 */
function formatDate(?string $date): string
{
    if (empty($date)) {
        return '-';
    }

    return date('d M Y', strtotime($date));
}

/**
 * Format date and time.
 *
 * @param string|null $datetime
 * @return string
 */
function formatDateTime(?string $datetime): string
{
    if (empty($datetime)) {
        return '-';
    }

    return date('d M Y H:i', strtotime($datetime));
}

/**
 * Calculate age from date of birth.
 *
 * @param string|null $dob
 * @return int|string
 */
function calculateAge(?string $dob)
{
    if (empty($dob)) {
        return '-';
    }

    $birthDate = new DateTime($dob);
    $today = new DateTime();

    return $birthDate->diff($today)->y;
}

/**
 * Display gender with fallback.
 *
 * @param string|null $gender
 * @return string
 */
function gender(?string $gender): string
{
    return $gender ?: '-';
}

/**
 * Generate hospital number.
 *
 * Example:
 * HSP-2026-000001
 *
 * @param int $id
 * @return string
 */
function generateHospitalNumber(int $id): string
{
    return sprintf(

        'HSP-%s-%06d',

        date('Y'),

        $id

    );
}

/**
 * Generate encounter number.
 *
 * Example:
 * ENC-2026-000045
 *
 * @param int $visitId
 * @return string
 */
function generateEncounterNumber(int $visitId): string
{
    return sprintf(

        'ENC-%s-%06d',

        date('Y'),

        $visitId

    );
}

/**
 * Generate employee number.
 *
 * Example:
 * EMP-000123
 *
 * @param int $id
 * @return string
 */
function generateEmployeeNumber(int $id): string
{
    return sprintf(

        'EMP-%06d',

        $id

    );
}

/**
 * Format currency.
 *
 * @param float|int $amount
 * @return string
 */
function money($amount): string
{
    return '₦' . number_format((float)$amount, 2);
}

/**
 * Determine whether the request is POST.
 *
 * @return bool
 */
function isPost(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Determine whether the request is GET.
 *
 * @return bool
 */
function isGet(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

/**
 * Get client IP address.
 *
 * @return string
 */
function clientIp(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}

/**
 * Return the current CSRF token, creating it when necessary.
 */
function csrfToken(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string)$_SESSION['csrf_token'];
}

/**
 * Render a reusable hidden CSRF form field.
 */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . e(csrfToken())
        . '">';
}

/**
 * Verify a submitted CSRF token without exposing token details.
 */
function verifyCsrfToken(?string $token = null): bool
{
    $token = $token ?? ($_POST['csrf_token'] ?? '');
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    return is_string($token)
        && is_string($sessionToken)
        && $token !== ''
        && $sessionToken !== ''
        && hash_equals($sessionToken, $token);
}

/**
 * Enforce CSRF validation for state-changing endpoints.
 */
function requireCsrfToken(?int $visitId = null): void
{
    if (!verifyCsrfToken()) {
        securityFailure(
            'Security validation failed. Please submit the form again.',
            $visitId,
            'INVALID_CSRF'
        );
    }
}

/**
 * Rotate the CSRF token after authentication or other trust-boundary changes.
 */
function rotateCsrfToken(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    return (string)$_SESSION['csrf_token'];
}

/**
 * Store and audit a security rejection when an audit service is available.
 */
function securityFailure(
    string $message,
    ?int $visitId = null,
    string $action = 'SECURITY_DENIED'
): never {
    $_SESSION['error_message'] = $message;

    if (isset($GLOBALS['pdo'])) {
        require_once __DIR__ . '/../services/AuditService.php';

        $userId = isset($_SESSION['user']['id'])
            ? (int)$_SESSION['user']['id']
            : null;

        (new AuditService($GLOBALS['pdo']))->log(
            $userId,
            $visitId,
            'Security',
            $action,
            $message
        );
    }

    http_response_code(403);

    exit($message);
}

function hmsHandwritingPrefix(): string
{
    return '__HMS_HANDWRITING_V1__';
}

function hmsExtractHandwriting(string $value): ?array
{
    $prefix = hmsHandwritingPrefix();
    if (!str_starts_with($value, $prefix)) {
        return null;
    }

    $payload = json_decode(substr($value, strlen($prefix)), true);
    if (!is_array($payload) || !isset($payload['strokes']) || !is_array($payload['strokes'])) {
        return null;
    }

    return $payload;
}

function hmsRenderNarrative(mixed $value, string $emptyText = 'Not recorded.'): void
{
    $value = (string)$value;
    if (trim($value) === '') {
        echo '<p class="text-muted">' . e($emptyText) . '</p>';
        return;
    }

    $handwriting = hmsExtractHandwriting($value);
    if ($handwriting === null) {
        echo '<p>' . nl2br(e($value)) . '</p>';
        return;
    }

    $width = max(320, min(1400, (int)($handwriting['width'] ?? 900)));
    $height = max(180, min(900, (int)($handwriting['height'] ?? 280)));
    $paths = [];

    foreach ($handwriting['strokes'] as $stroke) {
        if (!is_array($stroke) || count($stroke) < 1) {
            continue;
        }

        $points = [];
        foreach ($stroke as $point) {
            if (!is_array($point) || count($point) < 2) {
                continue;
            }

            $x = max(0, min($width, (float)$point[0]));
            $y = max(0, min($height, (float)$point[1]));
            $points[] = [round($x, 1), round($y, 1)];
        }

        if ($points === []) {
            continue;
        }

        if (count($points) === 1) {
            $x = $points[0][0];
            $y = $points[0][1];
            $paths[] = 'M ' . $x . ' ' . $y . ' m -1.8 0 a 1.8 1.8 0 1 0 3.6 0 a 1.8 1.8 0 1 0 -3.6 0';
            continue;
        }

        $path = 'M ' . $points[0][0] . ' ' . $points[0][1];
        for ($index = 1, $count = count($points); $index < $count; $index++) {
            $previous = $points[$index - 1];
            $current = $points[$index];
            $middleX = round(($previous[0] + $current[0]) / 2, 1);
            $middleY = round(($previous[1] + $current[1]) / 2, 1);
            $path .= ' Q ' . $previous[0] . ' ' . $previous[1] . ' ' . $middleX . ' ' . $middleY;
        }
        $last = $points[count($points) - 1];
        $path .= ' L ' . $last[0] . ' ' . $last[1];
        $paths[] = $path;
    }

    if ($paths === []) {
        echo '<p class="text-muted">No handwritten content captured.</p>';
        return;
    }

    echo '<div class="consultation-handwriting-view" role="img" aria-label="Handwritten clinical note">';
    echo '<svg viewBox="0 0 ' . $width . ' ' . $height . '" preserveAspectRatio="xMidYMin meet">';
    foreach ($paths as $path) {
        echo '<path d="' . e($path) . '" fill="none" stroke="#0f172a" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round" />';
    }
    echo '</svg>';
    echo '</div>';
}

function hmsRenderHandwritingTextarea(
    string $name,
    string $label,
    mixed $value = '',
    int $rows = 4,
    bool $required = false,
    bool $enableWritingMode = false,
    int $maxLength = 0
): void {
    $id = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $name) ?: $name;
    $requiredAttribute = $required ? ' required' : '';
    $maxLengthAttribute = $maxLength > 0 ? ' maxlength="' . (int)$maxLength . '"' : '';
    $handwritingAttribute = $enableWritingMode ? ' data-handwriting-input="1"' : '';

    echo '<div class="form-group consultation-writing-field">';
    echo '<label for="' . e($id) . '">' . e($label) . '</label>';
    echo '<textarea id="' . e($id) . '" name="' . e($name) . '" class="consultation-textarea" rows="' . (int)$rows . '"' . $requiredAttribute . $maxLengthAttribute . $handwritingAttribute . '>' . e((string)$value) . '</textarea>';

    if ($enableWritingMode) {
        echo '<div class="consultation-handwriting-pad" hidden>';
        echo '<div class="handwriting-pad-top"><span>Write with mouse, touch, or stylus</span><span class="text-muted">Saved safely as handwriting strokes for this field.</span></div>';
        echo '<canvas class="handwriting-canvas" width="1000" height="360" aria-label="' . e($label) . ' handwriting pad"></canvas>';
        echo '<div class="handwriting-pad-actions">';
        echo '<button type="button" class="btn-secondary btn-small" data-handwriting-undo>Undo Stroke</button>';
        echo '<button type="button" class="btn-secondary btn-small" data-handwriting-clear>Clear Pad</button>';
        echo '</div></div>';
    }

    echo '</div>';
}

function hmsRenderHandwritingToolbar(bool $enableWritingMode, string $title = 'Entry Mode'): void
{
    if (!$enableWritingMode) {
        return;
    }

    echo '<div class="consultation-writing-toolbar">';
    echo '<div><h3>' . e($title) . '</h3><p>Type normally, or switch to writing mode for a larger handwriting area.</p></div>';
    echo '<div class="writing-mode-switch" role="group" aria-label="Entry mode">';
    echo '<button type="button" class="writing-mode-option active" data-consultation-mode="type">Type</button>';
    echo '<button type="button" class="writing-mode-option" data-consultation-mode="write">Write</button>';
    echo '</div></div>';
}

function hmsRenderHandwritingScript(bool $enableWritingMode): void
{
    if (!$enableWritingMode) {
        return;
    }

    $prefix = json_encode(hmsHandwritingPrefix(), JSON_THROW_ON_ERROR);
    echo <<<HTML
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form[data-hms-handwriting-form="1"]').forEach(function (form) {
        const handwritingPrefix = {$prefix};
        const buttons = form.querySelectorAll('[data-consultation-mode]');
        const fields = form.querySelectorAll('.consultation-writing-field');
        let activeMode = 'type';

        function parseHandwriting(value) {
            if (!value || !value.startsWith(handwritingPrefix)) return null;
            try {
                const payload = JSON.parse(value.slice(handwritingPrefix.length));
                return payload && Array.isArray(payload.strokes) ? payload : null;
            } catch (error) {
                return null;
            }
        }

        function redrawCanvas(canvas, payload) {
            const context = canvas.getContext('2d');
            const width = Number(canvas.dataset.logicalWidth || 900);
            const height = Number(canvas.dataset.logicalHeight || 280);
            context.clearRect(0, 0, width, height);
            context.save();
            context.strokeStyle = 'rgba(37,99,235,.12)';
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
            context.lineWidth = 3.8;
            context.strokeStyle = '#0f172a';
            context.lineCap = 'round';
            context.lineJoin = 'round';
            (payload.strokes || []).forEach(function (stroke) {
                if (!Array.isArray(stroke) || stroke.length === 0) return;
                context.beginPath();
                if (stroke.length === 1) {
                    const dotX = Number(stroke[0][0]) * scaleX;
                    const dotY = Number(stroke[0][1]) * scaleY;
                    context.arc(dotX, dotY, 2.1, 0, Math.PI * 2);
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
            redrawCanvas(canvas, payload || canvas._handwritingPayload || {strokes: []});
        }

        function syncTextarea(field) {
            const textarea = field.querySelector('textarea[data-handwriting-input]');
            const canvas = field.querySelector('.handwriting-canvas');
            if (!textarea || !canvas || !canvas._handwritingPayload || activeMode !== 'write') return;
            const strokes = canvas._handwritingPayload.strokes || [];
            const payload = {
                width: Number(canvas.dataset.logicalWidth || 900),
                height: Number(canvas.dataset.logicalHeight || 280),
                strokes: strokes
            };
            textarea.value = strokes.length > 0 ? handwritingPrefix + JSON.stringify(payload) : '';
        }

        function setupPad(field) {
            const textarea = field.querySelector('textarea[data-handwriting-input]');
            const pad = field.querySelector('.consultation-handwriting-pad');
            const canvas = field.querySelector('.handwriting-canvas');
            if (!textarea || !pad || !canvas) return false;
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
                if (!from || !to) return;
                const context = canvas.getContext('2d');
                context.save();
                context.lineWidth = 4.4;
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
                if (activeMode !== 'write') return;
                event.preventDefault();
                event.stopPropagation();
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
                if (!drawing || !currentStroke) return;
                event.preventDefault();
                event.stopPropagation();
                const point = pointFromEvent(event);
                const previous = currentStroke[currentStroke.length - 1];
                const dx = point[0] - previous[0];
                const dy = point[1] - previous[1];
                if (Math.sqrt(dx * dx + dy * dy) < 0.8) return;
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
                        try { canvas.releasePointerCapture(event.pointerId); } catch (error) {}
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
            canvas.addEventListener('touchstart', function (event) { if (activeMode === 'write') event.preventDefault(); }, {passive: false});
            canvas.addEventListener('touchmove', function (event) { if (activeMode === 'write') event.preventDefault(); }, {passive: false});
            field.querySelector('[data-handwriting-undo]')?.addEventListener('click', function () {
                canvas._handwritingPayload.strokes.pop();
                redrawCanvas(canvas, canvas._handwritingPayload);
                syncTextarea(field);
            });
            field.querySelector('[data-handwriting-clear]')?.addEventListener('click', function () {
                canvas._handwritingPayload.strokes = [];
                redrawCanvas(canvas, canvas._handwritingPayload);
                syncTextarea(field);
            });
            return canvas._handwritingPayload.strokes.length > 0;
        }

        let hasHandwriting = false;
        fields.forEach(function (field) { hasHandwriting = setupPad(field) || hasHandwriting; });

        function setMode(mode) {
            activeMode = mode;
            const writing = mode === 'write';
            buttons.forEach(function (button) {
                button.classList.toggle('active', button.dataset.consultationMode === mode);
            });
            fields.forEach(function (field) {
                const pad = field.querySelector('.consultation-handwriting-pad');
                const textarea = field.querySelector('textarea[data-handwriting-input]');
                field.classList.toggle('writing-pad-active', writing);
                if (pad) pad.hidden = !writing;
                if (textarea) {
                    if (writing && textarea.required) {
                        textarea.dataset.wasRequired = '1';
                        textarea.required = false;
                    } else if (!writing && textarea.dataset.wasRequired === '1') {
                        textarea.required = true;
                    }
                    if (writing && !textarea.value.startsWith(handwritingPrefix)) textarea.dataset.typedValue = textarea.value;
                    if (!writing && textarea.value.startsWith(handwritingPrefix)) textarea.value = textarea.dataset.typedValue || '';
                    textarea.hidden = writing;
                }
                if (writing) {
                    const canvas = field.querySelector('.handwriting-canvas');
                    if (canvas) resizeCanvas(canvas);
                    syncTextarea(field);
                }
            });
        }

        buttons.forEach(function (button) {
            button.addEventListener('click', function () { setMode(button.dataset.consultationMode || 'type'); });
        });
        window.addEventListener('resize', function () {
            if (activeMode === 'write') fields.forEach(function (field) {
                const canvas = field.querySelector('.handwriting-canvas');
                if (canvas) resizeCanvas(canvas);
            });
        });
        form.addEventListener('submit', function () {
            if (activeMode === 'write') fields.forEach(syncTextarea);
        });
        if (hasHandwriting) setMode('write');
    });
});
</script>
HTML;
}
function field(
    string $name,
    array $patient,
    string $default = ''
): string {

    return e((string)($patient[$name] ?? $default));

}

function selected(
    string $name,
    string $value,
    array $patient
): string {

    return (($patient[$name] ?? '') === $value)
        ? 'selected'
        : '';

}
