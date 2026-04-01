<?php
/**
 * Render a breadcrumb trail.
 *
 * $items: array of ['label' => string, 'href' => ?string]
 * - If href is null/missing, item is rendered as the active (current) crumb.
 */
function renderBreadcrumb(array $items): void
{
    if (empty($items)) {
        return;
    }

    echo '<nav aria-label="breadcrumb" class="mt-3">';
    echo '<ol class="breadcrumb justify-content-center mb-0" style="background-color: #fff; padding: 10px 14px; border-radius: 6px;">';

    $lastIdx = count($items) - 1;
    foreach ($items as $idx => $item) {
        $label = isset($item['label']) ? (string)$item['label'] : '';
        $href  = $item['href'] ?? null;

        $safeLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        $isActive  = ($idx === $lastIdx) || empty($href);

        if ($isActive) {
            echo '<li class="breadcrumb-item active" aria-current="page">' . $safeLabel . '</li>';
        } else {
            $safeHref = htmlspecialchars((string)$href, ENT_QUOTES, 'UTF-8');
            echo '<li class="breadcrumb-item"><a href="' . $safeHref . '">' . $safeLabel . '</a></li>';
        }
    }

    echo '</ol>';
    echo '</nav>';
}