<?php

namespace App\Numz\WHMCS;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Blade;

/**
 * Smarty Template Engine Compatibility Layer
 *
 * Provides Smarty-like interface for WHMCS module templates
 * Uses Laravel Blade under the hood with Smarty syntax conversion
 */
class SmartyCompat
{
    protected array $templateDirs = [];
    protected array $compiledDirs = [];
    protected array $assignedVars = [];
    protected string $cacheDir;
    protected bool $caching = false;
    protected int $cacheLifetime = 3600;
    protected bool $forceCompile = false;
    protected string $leftDelimiter = '{';
    protected string $rightDelimiter = '}';

    public function __construct()
    {
        $this->templateDirs = [
            base_path('resources/views/whmcs'),
            base_path('templates'),
            base_path('modules'),
        ];

        $this->compiledDirs = [
            storage_path('framework/views'),
        ];

        $this->cacheDir = storage_path('framework/cache');
    }

    /**
     * Set template directory
     */
    public function setTemplateDir($dir): void
    {
        if (is_array($dir)) {
            $this->templateDirs = $dir;
        } else {
            $this->templateDirs = [$dir];
        }
    }

    /**
     * Add template directory
     */
    public function addTemplateDir($dir): void
    {
        if (!in_array($dir, $this->templateDirs)) {
            $this->templateDirs[] = $dir;
        }
    }

    /**
     * Get template directory
     */
    public function getTemplateDir(): array
    {
        return $this->templateDirs;
    }

    /**
     * Set compile directory
     */
    public function setCompileDir($dir): void
    {
        $this->compiledDirs = [$dir];
    }

    /**
     * Set cache directory
     */
    public function setCacheDir($dir): void
    {
        $this->cacheDir = $dir;
    }

    /**
     * Set caching
     */
    public function setCaching($caching): void
    {
        $this->caching = (bool) $caching;
    }

    /**
     * Set cache lifetime
     */
    public function setCacheLifetime($lifetime): void
    {
        $this->cacheLifetime = (int) $lifetime;
    }

    /**
     * Set force compile
     */
    public function setForceCompile($force): void
    {
        $this->forceCompile = (bool) $force;
    }

    /**
     * Assign variable
     */
    public function assign($key, $value = null): void
    {
        if (is_array($key)) {
            $this->assignedVars = array_merge($this->assignedVars, $key);
        } else {
            $this->assignedVars[$key] = $value;
        }
    }

    /**
     * Get assigned variable
     */
    public function getTemplateVars($varname = null)
    {
        if ($varname === null) {
            return $this->assignedVars;
        }

        return $this->assignedVars[$varname] ?? null;
    }

    /**
     * Clear assigned variables
     */
    public function clearAllAssign(): void
    {
        $this->assignedVars = [];
    }

    /**
     * Clear cache
     */
    public function clearAllCache(): void
    {
        // Clear Laravel view cache
        \Artisan::call('view:clear');
    }

    /**
     * Clear compiled templates
     */
    public function clearCompiledTemplate(): void
    {
        \Artisan::call('view:clear');
    }

    /**
     * Display template
     */
    public function display($template): void
    {
        echo $this->fetch($template);
    }

    /**
     * Fetch template output
     */
    public function fetch($template): string
    {
        // Find template file
        $templateFile = $this->findTemplate($template);

        if (!$templateFile) {
            throw new \Exception("Template '{$template}' not found in: " . implode(', ', $this->templateDirs));
        }

        // Convert Smarty template to Blade if needed
        $bladeContent = $this->convertSmartyToBlade($templateFile);

        // Create temporary Blade view
        $tempViewName = 'whmcs_' . md5($templateFile . filemtime($templateFile));
        $tempViewPath = storage_path('framework/views/' . $tempViewName . '.blade.php');

        // Write converted template
        if (!file_exists($tempViewPath) || $this->forceCompile || filemtime($templateFile) > filemtime($tempViewPath)) {
            file_put_contents($tempViewPath, $bladeContent);
        }

        // Render using Blade
        try {
            return view()->file($tempViewPath, $this->assignedVars)->render();
        } catch (\Exception $e) {
            \Log::error('Smarty template rendering failed', [
                'template' => $template,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Find template file
     */
    protected function findTemplate($template): ?string
    {
        // Remove .tpl extension if present for search
        $templateBase = preg_replace('/\.tpl$/', '', $template);

        foreach ($this->templateDirs as $dir) {
            // Try with .tpl extension
            $tplPath = $dir . '/' . $templateBase . '.tpl';
            if (file_exists($tplPath)) {
                return $tplPath;
            }

            // Try without extension
            $path = $dir . '/' . $template;
            if (file_exists($path)) {
                return $path;
            }

            // Try with .blade.php extension
            $bladePath = $dir . '/' . $templateBase . '.blade.php';
            if (file_exists($bladePath)) {
                return $bladePath;
            }
        }

        return null;
    }

    /**
     * Convert Smarty syntax to Blade syntax
     */
    protected function convertSmartyToBlade($templateFile): string
    {
        $content = file_get_contents($templateFile);

        // If already a Blade template, return as-is
        if (str_ends_with($templateFile, '.blade.php')) {
            return $content;
        }

        // Convert Smarty comments {* comment *} to Blade {{-- comment --}}
        $content = preg_replace('/\{\*(.*?)\*\}/s', '{{-- $1 --}}', $content);

        // Convert Smarty variables {$var} to Blade {{ $var }}
        $content = preg_replace('/\{\$([a-zA-Z0-9_\.\[\]\'\"]+)\}/', '{{ $$$1 }}', $content);

        // Convert {if} to @if
        $content = preg_replace('/\{if\s+(.+?)\}/', '@if($1)', $content);
        $content = preg_replace('/\{elseif\s+(.+?)\}/', '@elseif($1)', $content);
        $content = str_replace('{else}', '@else', $content);
        $content = str_replace('{/if}', '@endif', $content);

        // Convert {foreach} to @foreach
        $content = preg_replace('/\{foreach\s+from=\$([a-zA-Z0-9_]+)\s+item=([a-zA-Z0-9_]+)(?:\s+key=([a-zA-Z0-9_]+))?\}/',
            '@foreach($$$$1 as $3 => $$$2)', $content);
        $content = preg_replace('/\{foreach\s+\$([a-zA-Z0-9_]+)\s+as\s+\$([a-zA-Z0-9_]+)\}/',
            '@foreach($$$$1 as $$$2)', $content);
        $content = str_replace('{/foreach}', '@endforeach', $content);

        // Convert {section} to @for (approximation)
        $content = preg_replace('/\{section\s+name=([a-zA-Z0-9_]+)\s+loop=\$([a-zA-Z0-9_]+)\}/',
            '@foreach($$$$2 as $$$1)', $content);
        $content = str_replace('{/section}', '@endforeach', $content);

        // Convert {include} to @include
        $content = preg_replace('/\{include\s+file=["\'](.+?)["\']\}/', '@include(\'$1\')', $content);

        // Convert {literal} tags - content inside should not be parsed
        $content = preg_replace_callback('/\{literal\}(.*?)\{\/literal\}/s', function($matches) {
            return '@php echo "' . addslashes($matches[1]) . '"; @endphp';
        }, $content);

        // Convert function calls {funcname param=$value}
        $content = preg_replace('/\{([a-zA-Z0-9_]+)\s+(.+?)\}/', '@php $1(parseParams("$2")); @endphp', $content);

        // Convert array access $array.key to $array['key']
        $content = preg_replace_callback('/\$([a-zA-Z0-9_]+)\.([a-zA-Z0-9_]+)/', function($matches) {
            return '$' . $matches[1] . '[\'' . $matches[2] . '\']';
        }, $content);

        // Convert Smarty modifiers (basic support)
        // {$var|escape} to {{ e($var) }}
        $content = preg_replace('/\{\$([a-zA-Z0-9_\[\]]+)\|escape\}/', '{{ e($$$$1) }}', $content);
        $content = preg_replace('/\{\$([a-zA-Z0-9_\[\]]+)\|htmlspecialchars\}/', '{{ e($$$$1) }}', $content);

        // {$var|upper} to {{ strtoupper($var) }}
        $content = preg_replace('/\{\$([a-zA-Z0-9_\[\]]+)\|upper\}/', '{{ strtoupper($$$$1) }}', $content);
        $content = preg_replace('/\{\$([a-zA-Z0-9_\[\]]+)\|lower\}/', '{{ strtolower($$$$1) }}', $content);

        // {$var|date_format:"%Y-%m-%d"} to {{ date("Y-m-d", strtotime($var)) }}
        $content = preg_replace('/\{\$([a-zA-Z0-9_\[\]]+)\|date_format:"([^"]+)"\}/',
            '{{ date("$2", strtotime($$$$1)) }}', $content);

        return $content;
    }

    /**
     * Check if template exists
     */
    public function templateExists($template): bool
    {
        return $this->findTemplate($template) !== null;
    }

    /**
     * Register plugin
     */
    public function registerPlugin($type, $name, $callback): void
    {
        // Store plugin for later use
        // This is a simplified implementation
    }

    /**
     * Test installation
     */
    public function testInstall(): array
    {
        $errors = [];

        foreach ($this->compiledDirs as $dir) {
            if (!is_dir($dir)) {
                $errors[] = "Compile directory does not exist: {$dir}";
            } elseif (!is_writable($dir)) {
                $errors[] = "Compile directory is not writable: {$dir}";
            }
        }

        if (!is_dir($this->cacheDir)) {
            $errors[] = "Cache directory does not exist: {$this->cacheDir}";
        } elseif (!is_writable($this->cacheDir)) {
            $errors[] = "Cache directory is not writable: {$this->cacheDir}";
        }

        return empty($errors) ? ['success' => true] : ['success' => false, 'errors' => $errors];
    }
}

/**
 * Create global Smarty instance for compatibility
 */
if (!class_exists('Smarty')) {
    class Smarty extends SmartyCompat {
        // Smarty class alias
    }
}
