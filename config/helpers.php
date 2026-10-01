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
            let activePointerId = null;
            let pageGesturesLocked = false;

            canvas.style.touchAction = 'none';
            canvas.style.userSelect = 'none';
            canvas.style.webkitUserSelect = 'none';
            canvas.style.webkitTouchCallout = 'none';

            function lockPageGestures() {
                pageGesturesLocked = true;
                if (!document.body.classList.contains('consultation-writing-in-progress')) {
                    const scrollY = window.scrollY || document.documentElement.scrollTop || 0;
                    document.body.dataset.handwritingScrollY = String(scrollY);
                    document.body.dataset.handwritingPreviousPosition = document.body.style.position || '';
                    document.body.dataset.handwritingPreviousTop = document.body.style.top || '';
                    document.body.dataset.handwritingPreviousLeft = document.body.style.left || '';
                    document.body.dataset.handwritingPreviousRight = document.body.style.right || '';
                    document.body.dataset.handwritingPreviousWidth = document.body.style.width || '';
                    document.body.style.position = 'fixed';
                    document.body.style.top = '-' + scrollY + 'px';
                    document.body.style.left = '0';
                    document.body.style.right = '0';
                    document.body.style.width = '100%';
                }
                document.documentElement.classList.add('consultation-writing-in-progress');
                document.body.classList.add('consultation-writing-in-progress');
            }

            function unlockPageGestures() {
                if (!pageGesturesLocked && !document.body.classList.contains('consultation-writing-in-progress')) return;
                pageGesturesLocked = false;
                const scrollY = Number(document.body.dataset.handwritingScrollY || '0');
                document.documentElement.classList.remove('consultation-writing-in-progress');
                document.body.classList.remove('consultation-writing-in-progress');
                document.body.style.position = document.body.dataset.handwritingPreviousPosition || '';
                document.body.style.top = document.body.dataset.handwritingPreviousTop || '';
                document.body.style.left = document.body.dataset.handwritingPreviousLeft || '';
                document.body.style.right = document.body.dataset.handwritingPreviousRight || '';
                document.body.style.width = document.body.dataset.handwritingPreviousWidth || '';
                delete document.body.dataset.handwritingScrollY;
                delete document.body.dataset.handwritingPreviousPosition;
                delete document.body.dataset.handwritingPreviousTop;
                delete document.body.dataset.handwritingPreviousLeft;
                delete document.body.dataset.handwritingPreviousRight;
                delete document.body.dataset.handwritingPreviousWidth;
                if (scrollY > 0) window.scrollTo(0, scrollY);
            }

            function blockGesture(event) {
                if (activeMode !== 'write') return;
                event.preventDefault();
                event.stopPropagation();
            }

            function clientPointFromEvent(event) {
                if (event.touches && event.touches.length > 0) {
                    return {clientX: event.touches[0].clientX, clientY: event.touches[0].clientY};
                }
                if (event.changedTouches && event.changedTouches.length > 0) {
                    return {clientX: event.changedTouches[0].clientX, clientY: event.changedTouches[0].clientY};
                }
                return {clientX: event.clientX, clientY: event.clientY};
            }

            function pointFromEvent(event) {
                const rect = canvas.getBoundingClientRect();
                const clientPoint = clientPointFromEvent(event);
                const x = Math.max(0, Math.min(Number(canvas.dataset.logicalWidth || rect.width), clientPoint.clientX - rect.left));
                const y = Math.max(0, Math.min(Number(canvas.dataset.logicalHeight || rect.height), clientPoint.clientY - rect.top));
                return [Math.round(x * 10) / 10, Math.round(y * 10) / 10];
            }

            function shouldLockForEvent(event) {
                if (event.pointerType) return event.pointerType !== 'mouse';
                return Boolean(event.touches || event.changedTouches);
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
                if (event.touches && event.touches.length > 1) {
                    blockGesture(event);
                    return;
                }
                event.preventDefault();
                event.stopPropagation();
                if (typeof canvas.setPointerCapture === 'function' && event.pointerId !== undefined) {
                    canvas.setPointerCapture(event.pointerId);
                    activePointerId = event.pointerId;
                }
                lastPoint = pointFromEvent(event);
                if (shouldLockForEvent(event)) lockPageGestures();
                drawing = true;
                currentStroke = [lastPoint];
                canvas._handwritingPayload.strokes.push(currentStroke);
                drawSegment([lastPoint[0] - 0.1, lastPoint[1] - 0.1], lastPoint);
                syncTextarea(field);
            }

            function continueStroke(event) {
                if (!drawing || !currentStroke) return;
                if (activePointerId !== null && event.pointerId !== undefined && event.pointerId !== activePointerId) return;
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
                if (!drawing && !currentStroke) return;
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
                activePointerId = null;
                unlockPageGestures();
                redrawCanvas(canvas, canvas._handwritingPayload);
                syncTextarea(field);
            }

            const supportsPointerEvents = 'PointerEvent' in window;
            canvas.addEventListener('pointerdown', beginStroke, {passive: false});
            document.addEventListener('pointermove', continueStroke, {passive: false});
            ['pointerup', 'pointercancel', 'lostpointercapture'].forEach(function (eventName) {
                document.addEventListener(eventName, endStroke, {passive: false});
            });
            canvas.addEventListener('touchstart', function (event) {
                if (supportsPointerEvents) {
                    blockGesture(event);
                    return;
                }
                beginStroke(event);
            }, {passive: false});
            canvas.addEventListener('touchmove', function (event) {
                if (supportsPointerEvents) {
                    blockGesture(event);
                    return;
                }
                continueStroke(event);
            }, {passive: false});
            canvas.addEventListener('touchend', function (event) {
                if (supportsPointerEvents) {
                    blockGesture(event);
                    return;
                }
                endStroke(event);
            }, {passive: false});
            canvas.addEventListener('touchcancel', function (event) {
                if (supportsPointerEvents) {
                    blockGesture(event);
                    return;
                }
                endStroke(event);
            }, {passive: false});
            document.addEventListener('touchmove', function (event) {
                if (document.body.classList.contains('consultation-writing-in-progress')) {
                    event.preventDefault();
                    event.stopPropagation();
                }
            }, {passive: false});
            document.addEventListener('touchend', function (event) {
                if (document.body.classList.contains('consultation-writing-in-progress')) {
                    if (drawing) {
                        endStroke(event);
                    } else {
                        event.preventDefault();
                        event.stopPropagation();
                        unlockPageGestures();
                    }
                }
            }, {passive: false});
            document.addEventListener('touchcancel', function (event) {
                if (document.body.classList.contains('consultation-writing-in-progress')) {
                    if (drawing) {
                        endStroke(event);
                    } else {
                        event.preventDefault();
                        event.stopPropagation();
                        unlockPageGestures();
                    }
                }
            }, {passive: false});
            canvas.addEventListener('gesturestart', blockGesture, {passive: false});
            canvas.addEventListener('gesturechange', blockGesture, {passive: false});
            canvas.addEventListener('gestureend', blockGesture, {passive: false});
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
            if (document.body.classList.contains('consultation-writing-in-progress')) return;
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

function hmsRenderConfiguredFields(array $fields, array $values = []): void
{
    if ($fields === []) {
        return;
    }

    echo '<div class="form-section">';
    echo '<h3>Additional Configured Fields</h3>';
    echo '<p class="text-muted">These optional fields are controlled from Administration &rarr; Form Settings.</p>';
    echo '<div class="form-grid">';

    foreach ($fields as $field) {
        $fieldKey = (string)($field['field_key'] ?? '');
        if ($fieldKey === '') {
            continue;
        }

        $fieldName = 'configured_fields[' . $fieldKey . ']';
        $fieldId = 'configured_' . (preg_replace('/[^a-zA-Z0-9_-]+/', '_', $fieldKey) ?: $fieldKey);
        $fieldLabel = (string)($field['field_label'] ?? $fieldKey);
        $fieldType = (string)($field['field_type'] ?? 'text');
        $fieldValue = (string)($values[$fieldKey] ?? '');
        $requiredAttribute = !empty($field['is_required']) ? ' required' : '';
        $options = [];
        if (!empty($field['options_json'])) {
            $decoded = json_decode((string)$field['options_json'], true);
            $options = is_array($decoded) ? $decoded : [];
        }

        echo '<div class="form-group">';
        echo '<label for="' . e($fieldId) . '">' . e($fieldLabel) . '</label>';

        if ($fieldType === 'textarea') {
            echo '<textarea id="' . e($fieldId) . '" name="' . e($fieldName) . '" rows="4"' . $requiredAttribute . '>' . e($fieldValue) . '</textarea>';
        } elseif ($fieldType === 'number') {
            echo '<input id="' . e($fieldId) . '" name="' . e($fieldName) . '" type="number" step="any" value="' . e($fieldValue) . '"' . $requiredAttribute . '>';
        } elseif ($fieldType === 'date') {
            echo '<input id="' . e($fieldId) . '" name="' . e($fieldName) . '" type="date" value="' . e($fieldValue) . '"' . $requiredAttribute . '>';
        } elseif ($fieldType === 'select') {
            echo '<select id="' . e($fieldId) . '" name="' . e($fieldName) . '"' . $requiredAttribute . '>';
            echo '<option value="">Select ' . e($fieldLabel) . '</option>';
            foreach ($options as $option) {
                $option = (string)$option;
                echo '<option value="' . e($option) . '"' . ($fieldValue === $option ? ' selected' : '') . '>' . e($option) . '</option>';
            }
            echo '</select>';
        } elseif ($fieldType === 'checkbox') {
            echo '<label class="checkbox-inline"><input id="' . e($fieldId) . '" name="' . e($fieldName) . '" type="checkbox" value="1"' . ($fieldValue === 'Yes' ? ' checked' : '') . '> Yes</label>';
        } elseif ($fieldType === 'yes_no') {
            echo '<select id="' . e($fieldId) . '" name="' . e($fieldName) . '"' . $requiredAttribute . '>';
            echo '<option value="">Select</option>';
            echo '<option value="Yes"' . ($fieldValue === 'Yes' ? ' selected' : '') . '>Yes</option>';
            echo '<option value="No"' . ($fieldValue === 'No' ? ' selected' : '') . '>No</option>';
            echo '</select>';
        } else {
            echo '<input id="' . e($fieldId) . '" name="' . e($fieldName) . '" type="text" value="' . e($fieldValue) . '"' . $requiredAttribute . '>';
        }

        echo '</div>';
    }

    echo '</div></div>';
}

function hmsRenderConfiguredValues(array $values): void
{
    if ($values === []) {
        return;
    }

    echo '<div class="card">';
    echo '<h3>Additional Configured Fields</h3>';
    echo '<table><tbody>';
    foreach ($values as $value) {
        echo '<tr><th>' . e((string)($value['field_label'] ?? $value['field_key'] ?? 'Configured Field')) . '</th><td>';
        hmsRenderNarrative((string)($value['value_text'] ?? '-'));
        echo '</td></tr>';
    }
    echo '</tbody></table>';
    echo '</div>';
}

function hmsBillableItemOptions(PDO $pdo, ?int $departmentId = null): array
{
    try {
        $where = ['is_active = 1'];
        $params = [];
        if ($departmentId !== null && $departmentId > 0) {
            $where[] = 'department_id = :department_id';
            $params[':department_id'] = $departmentId;
        }

        $stmt = $pdo->prepare(
            'SELECT id, item_code, item_name, unit_price
             FROM billable_items
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY item_name ASC, id ASC'
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable) {
        return [];
    }
}

function hmsDepartmentIdByName(PDO $pdo, array $departmentNames): int
{
    foreach ($departmentNames as $departmentName) {
        $stmt = $pdo->prepare('SELECT id FROM departments WHERE department_name = :name AND is_active = 1 LIMIT 1');
        $stmt->execute([':name' => (string)$departmentName]);
        $id = (int)($stmt->fetchColumn() ?: 0);
        if ($id > 0) {
            return $id;
        }
    }

    return 0;
}

function hmsRenderBillableItemSelect(array $items, $selectedId = null, string $label = 'Billable Item'): void
{
    $selectedIds = [];
    if (is_array($selectedId)) {
        $selectedIds = array_values(array_filter(array_map('intval', $selectedId), static fn (int $id): bool => $id > 0));
    } else {
        $selected = (int)($selectedId ?? 0);
        if ($selected > 0) {
            $selectedIds[] = $selected;
        }
    }
    if ($selectedIds === []) {
        $selectedIds[] = 0;
    }

    $options = '<option value="">Select billable item</option>';
    foreach ($items as $item) {
        $id = (int)($item['id'] ?? 0);
        $text = trim((string)($item['item_code'] ?? '') . ' - ' . (string)($item['item_name'] ?? ''));
        $price = number_format((float)($item['unit_price'] ?? 0), 2);
        $options .= '<option value="' . $id . '">' . e($text . ' (₦' . $price . ')') . '</option>';
    }

    $templateId = 'billable-item-template-' . bin2hex(random_bytes(3));

    echo '<div class="form-group billable-item-picker" data-billable-item-picker>';
    echo '<label>' . e($label) . ' <span class="required">*</span></label>';
    echo '<div class="billable-item-rows" data-billable-item-rows>';
    foreach ($selectedIds as $index => $selected) {
        echo '<div class="billable-item-row" data-billable-item-row style="display:flex;gap:.5rem;align-items:center;margin-bottom:.5rem;">';
        echo '<select name="suggested_billable_item_ids[]" ' . ($index === 0 ? 'required' : '') . ' style="flex:1;">';
        echo str_replace('value="' . (int)$selected . '"', 'value="' . (int)$selected . '" selected', $options);
        echo '</select>';
        echo '<button type="button" class="btn-secondary" data-remove-billable-item title="Remove billable item" style="border-radius:999px;width:2rem;height:2rem;padding:0;' . ($index === 0 ? 'visibility:hidden;' : '') . '">&times;</button>';
        echo '</div>';
    }
    echo '</div>';
    echo '<button type="button" class="btn-primary" data-add-billable-item title="Add another billable item" style="border-radius:999px;width:2.75rem;height:2.75rem;padding:0;font-size:1.5rem;line-height:1;margin-top:.25rem;">+</button>';
    echo '<small class="form-help">Select every item Accounts should bill for this request before the receiving department starts work.</small>';
    echo '<template id="' . e($templateId) . '">';
    echo '<div class="billable-item-row" data-billable-item-row style="display:flex;gap:.5rem;align-items:center;margin-bottom:.5rem;">';
    echo '<select name="suggested_billable_item_ids[]" style="flex:1;">' . $options . '</select>';
    echo '<button type="button" class="btn-secondary" data-remove-billable-item title="Remove billable item" style="border-radius:999px;width:2rem;height:2rem;padding:0;">&times;</button>';
    echo '</div>';
    echo '</template>';
    echo '<script>(function(){var root=document.currentScript.closest("[data-billable-item-picker]");if(!root){return;}var rows=root.querySelector("[data-billable-item-rows]");var template=root.querySelector("template");var add=root.querySelector("[data-add-billable-item]");if(add&&rows&&template){add.addEventListener("click",function(){rows.appendChild(template.content.cloneNode(true));});}root.addEventListener("click",function(event){var button=event.target.closest("[data-remove-billable-item]");if(!button){return;}var row=button.closest("[data-billable-item-row]");if(row&&rows.querySelectorAll("[data-billable-item-row]").length>1){row.remove();}});})();</script>';
    echo '</div>';
}

function hmsRenderInventoryItemSelect(array $items, $selectedId = null, string $label = 'Medication / Inventory Item'): void
{
    $selectedIds = [];
    if (is_array($selectedId)) {
        $selectedIds = array_values(array_filter(array_map('intval', $selectedId), static fn (int $id): bool => $id > 0));
    } else {
        $selected = (int)($selectedId ?? 0);
        if ($selected > 0) {
            $selectedIds[] = $selected;
        }
    }
    if ($selectedIds === []) {
        $selectedIds[] = 0;
    }

    $options = '<option value="">Select an item or leave blank for free text</option>';
    foreach ($items as $item) {
        $id = (int)($item['id'] ?? 0);
        $unitPrice = $item['unit_price'] ?? null;
        $priceLabel = $unitPrice !== null ? ' — ₦' . number_format((float)$unitPrice, 2) : '';
        $stockLabel = isset($item['pharmacy_stock_available']) ? ' — Stock: ' . number_format((float)$item['pharmacy_stock_available'], 0) : '';
        $text = trim((string)($item['item_code'] ?? '') . ' - ' . (string)($item['item_name'] ?? ''));
        $options .= '<option value="' . $id . '">' . e($text . $priceLabel . $stockLabel) . '</option>';
    }

    $templateId = 'inventory-item-template-' . bin2hex(random_bytes(3));

    echo '<div class="form-group inventory-item-picker" data-inventory-item-picker>';
    echo '<label>' . e($label) . '</label>';
    echo '<div class="inventory-item-rows" data-inventory-item-rows>';
    foreach ($selectedIds as $index => $selected) {
        echo '<div class="inventory-item-row" data-inventory-item-row style="display:flex;gap:.5rem;align-items:center;margin-bottom:.5rem;">';
        echo '<select name="inventory_item_ids[]" ' . ($index === 0 ? 'id="inventory_item_id"' : '') . ' style="flex:1;">';
        echo str_replace('value="' . (int)$selected . '"', 'value="' . (int)$selected . '" selected', $options);
        echo '</select>';
        echo '<button type="button" class="btn-secondary" data-remove-inventory-item title="Remove item" style="border-radius:999px;width:2rem;height:2rem;padding:0;' . ($index === 0 ? 'visibility:hidden;' : '') . '">&times;</button>';
        echo '</div>';
    }
    echo '</div>';
    echo '<button type="button" class="btn-primary" data-add-inventory-item title="Add another medication or inventory item" style="border-radius:999px;width:2.75rem;height:2.75rem;padding:0;font-size:1.5rem;line-height:1;margin-top:.25rem;">+</button>';
    echo '<small class="form-help">Use the plus button to add multiple medications/items to the same pharmacy request.</small>';
    echo '<template id="' . e($templateId) . '">';
    echo '<div class="inventory-item-row" data-inventory-item-row style="display:flex;gap:.5rem;align-items:center;margin-bottom:.5rem;">';
    echo '<select name="inventory_item_ids[]" style="flex:1;">' . $options . '</select>';
    echo '<button type="button" class="btn-secondary" data-remove-inventory-item title="Remove item" style="border-radius:999px;width:2rem;height:2rem;padding:0;">&times;</button>';
    echo '</div>';
    echo '</template>';
    echo '<script>(function(){var root=document.currentScript.closest("[data-inventory-item-picker]");if(!root){return;}var rows=root.querySelector("[data-inventory-item-rows]");var template=root.querySelector("template");var add=root.querySelector("[data-add-inventory-item]");if(add&&rows&&template){add.addEventListener("click",function(){rows.appendChild(template.content.cloneNode(true));});}root.addEventListener("click",function(event){var button=event.target.closest("[data-remove-inventory-item]");if(!button){return;}var row=button.closest("[data-inventory-item-row]");if(row&&rows.querySelectorAll("[data-inventory-item-row]").length>1){row.remove();}});})();</script>';
    echo '</div>';
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
