<?php
// src/helpers/markdown.php

// Try to load Parsedown if available
$parsedownPath = __DIR__ . '/Parsedown.php';
if (file_exists($parsedownPath)) {
    require_once $parsedownPath;
    $Parsedown = new Parsedown();
    // enable safe mode to avoid raw HTML being output (Parsedown has setSafeMode in ParsedownExtra; in basic Parsedown you can escape HTML)
    // We'll escape HTML before parsing:
    function render_markdown(string $md): string {
        global $Parsedown;
        // Prevent raw HTML - escape then let Parsedown handle markdown
        $md = htmlspecialchars($md, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return $Parsedown->text($md);
    }
} else {
    // Fallback simple renderer: escapes HTML and converts newlines to <br>
    function render_markdown(string $md): string {
        $safe = htmlspecialchars($md, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');
        // basic bold/italic support (very simple)
        $safe = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $safe);
        $safe = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $safe);
        return nl2br($safe);
    }
}
