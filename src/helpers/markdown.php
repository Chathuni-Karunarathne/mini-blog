
<?php

// src/helpers/markdown.php - Markdown rendering with Parsedown library

// Get the path to Parsedown library
$parsedownPath = __DIR__ . '/Parsedown.php';

// Load and initialize Parsedown
require_once $parsedownPath;
$Parsedown = new Parsedown();

// Render markdown safely by escaping HTML first, then parsing markdown
function render_markdown(string $md): string {
    global $Parsedown;
    // Escape HTML characters to prevent XSS attacks before markdown parsing
    $md = htmlspecialchars($md, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');
    // Convert markdown syntax to HTML
    return $Parsedown->text($md);
}